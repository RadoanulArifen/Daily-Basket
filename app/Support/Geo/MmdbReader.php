<?php

namespace App\Support\Geo;

use RuntimeException;

/**
 * A small reader for MaxMind DB (.mmdb) files, such as DB-IP's free "IP to City Lite".
 *
 * Built in so the store needs no extra Composer package. It reads the file from disk as it
 * goes (the City Lite file is over 100 MB, so it is never loaded into memory) and follows
 * the published format: https://maxmind.github.io/MaxMind-DB/
 */
class MmdbReader
{
    private const MARKER = "\xAB\xCD\xEFMaxMind.com";

    /** @var resource */
    private $handle;
    private int $nodeCount;
    private int $recordSize;
    private int $nodeBytes;
    private int $treeSize;
    private int $dataStart;
    private int $ipVersion;
    private ?int $ipv4Start = null;

    /** @var array<string, mixed> */
    public readonly array $metadata;

    public function __construct(private string $path)
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot open the IP database at {$path}.");
        }
        $this->handle = $handle;

        $size = (int) fstat($handle)['size'];
        $tailSize = min($size, 128 * 1024);
        $tail = $this->read($size - $tailSize, $tailSize);
        $at = strrpos($tail, self::MARKER);
        if ($at === false) {
            throw new RuntimeException('This is not a MaxMind DB (.mmdb) file.');
        }

        $metaStart = $size - $tailSize + $at + strlen(self::MARKER);
        [$metadata] = $this->decode($metaStart, $metaStart);
        if (! is_array($metadata) || ! isset($metadata['node_count'], $metadata['record_size'], $metadata['ip_version'])) {
            throw new RuntimeException('The IP database has no readable metadata.');
        }

        $this->metadata = $metadata;
        $this->nodeCount = (int) $metadata['node_count'];
        $this->recordSize = (int) $metadata['record_size'];
        if (! in_array($this->recordSize, [24, 28, 32], true)) {
            throw new RuntimeException("Unsupported record size {$this->recordSize}.");
        }
        $this->ipVersion = (int) $metadata['ip_version'];
        $this->nodeBytes = $this->recordSize * 2 / 8;
        $this->treeSize = $this->nodeBytes * $this->nodeCount;
        $this->dataStart = $this->treeSize + 16;
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /** The record for an IP address, or null when the database has none. */
    public function get(string $ip): mixed
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            throw new \InvalidArgumentException("Not an IP address: {$ip}");
        }

        $bits = strlen($packed) * 8;
        if ($bits === 128 && $this->ipVersion === 4) {
            return null;   // an IPv4-only database knows nothing about IPv6
        }

        $node = ($bits === 32 && $this->ipVersion === 6) ? $this->ipv4Start() : 0;

        for ($i = 0; $i < $bits && $node < $this->nodeCount; $i++) {
            $bit = (ord($packed[$i >> 3]) >> (7 - ($i & 7))) & 1;
            $node = $this->record($node, $bit);
        }

        if ($node === $this->nodeCount) {
            return null;   // no data for this address
        }
        if ($node < $this->nodeCount) {
            throw new RuntimeException('The IP database search tree is broken.');
        }

        $offset = $node - $this->nodeCount + $this->treeSize;
        if ($offset >= $this->fileSize()) {
            throw new RuntimeException('The IP database points outside the file.');
        }

        return $this->decode($offset, $this->dataStart)[0];
    }

    private function ipv4Start(): int
    {
        if ($this->ipv4Start === null) {
            $node = 0;
            for ($i = 0; $i < 96 && $node < $this->nodeCount; $i++) {
                $node = $this->record($node, 0);
            }
            $this->ipv4Start = $node;
        }

        return $this->ipv4Start;
    }

    private function record(int $node, int $bit): int
    {
        $b = $this->read($node * $this->nodeBytes, $this->nodeBytes);

        return match ($this->recordSize) {
            24 => unpack('N', "\0".substr($b, $bit * 3, 3))[1],
            28 => $bit === 0
                ? (((ord($b[3]) & 0xF0) << 20) | unpack('N', "\0".substr($b, 0, 3))[1])
                : (((ord($b[3]) & 0x0F) << 24) | unpack('N', "\0".substr($b, 4, 3))[1]),
            32 => unpack('N', substr($b, $bit * 4, 4))[1],
        };
    }

    /**
     * Decodes one value at $offset. Pointers are relative to $base (the data section).
     *
     * @return array{0: mixed, 1: int} the value and the offset after it
     */
    private function decode(int $offset, int $base, int $depth = 0): array
    {
        if ($depth > 64) {
            throw new RuntimeException('The IP database nests too deeply.');
        }

        $ctrl = ord($this->read($offset++, 1));
        $type = $ctrl >> 5;

        if ($type === 1) {   // pointer
            $ss = ($ctrl >> 3) & 0x3;
            $vvv = $ctrl & 0x7;
            $bytes = $this->read($offset, $ss + 1);
            $offset += $ss + 1;
            $pointer = match ($ss) {
                0 => ($vvv << 8) | ord($bytes[0]),
                1 => (($vvv << 16) | unpack('n', $bytes)[1]) + 2048,
                2 => (($vvv << 24) | unpack('N', "\0".$bytes)[1]) + 526336,
                3 => unpack('N', $bytes)[1],
            };

            return [$this->decode($base + $pointer, $base, $depth + 1)[0], $offset];
        }

        if ($type === 0) {   // extended type
            $type = 7 + ord($this->read($offset++, 1));
        }

        $size = $ctrl & 0x1F;
        if ($size >= 29) {
            $extra = $size - 28;
            $n = unpack('N', str_pad($this->read($offset, $extra), 4, "\0", STR_PAD_LEFT))[1];
            $offset += $extra;
            $size = match ($extra) { 1 => 29 + $n, 2 => 285 + $n, default => 65821 + $n };
        }

        switch ($type) {
            case 2:   // UTF-8 string
                return [$size ? $this->read($offset, $size) : '', $offset + $size];
            case 3:   // double
                return [unpack('E', $this->read($offset, 8))[1], $offset + 8];
            case 4:   // bytes
                return [$size ? $this->read($offset, $size) : '', $offset + $size];
            case 5: case 6: case 9: case 10:   // unsigned integers
                return [$this->unsigned($size ? $this->read($offset, $size) : ''), $offset + $size];
            case 8:   // int32
                $v = $this->unsigned($size ? $this->read($offset, $size) : '');

                return [$size === 4 && $v >= 0x80000000 ? $v - 0x100000000 : $v, $offset + $size];
            case 7:   // map
                $map = [];
                for ($i = 0; $i < $size; $i++) {
                    [$key, $offset] = $this->decode($offset, $base, $depth + 1);
                    [$map[(string) $key], $offset] = $this->decode($offset, $base, $depth + 1);
                }

                return [$map, $offset];
            case 11:   // array
                $list = [];
                for ($i = 0; $i < $size; $i++) {
                    [$list[], $offset] = $this->decode($offset, $base, $depth + 1);
                }

                return [$list, $offset];
            case 14:   // boolean (the value is the size)
                return [$size !== 0, $offset];
            case 15:   // float
                return [unpack('G', $this->read($offset, 4))[1], $offset + 4];
            case 12: case 13:   // data cache container / end marker
                return [null, $offset];
        }

        throw new RuntimeException("Unknown data type {$type} in the IP database.");
    }

    private function unsigned(string $bytes): int|float
    {
        $v = 0;
        foreach (str_split($bytes) as $byte) {
            $v = $v * 256 + ord($byte);
        }

        return $v;
    }

    private function read(int $offset, int $length): string
    {
        if ($length === 0) {
            return '';
        }
        fseek($this->handle, $offset);
        $data = fread($this->handle, $length);
        if ($data === false || strlen($data) !== $length) {
            throw new RuntimeException('The IP database file is cut short.');
        }

        return $data;
    }

    private function fileSize(): int
    {
        return (int) fstat($this->handle)['size'];
    }
}

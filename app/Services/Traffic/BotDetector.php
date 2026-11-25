<?php

namespace App\Services\Traffic;

/** Sorts a visit into a person or one kind of bot, from the user agent. */
class BotDetector
{
    public const KINDS = ['search', 'ai', 'fake', 'other'];

    private const SEARCH = '/googlebot|google-inspectiontool|storebot-google|adsbot-google|mediapartners-google|bingbot|bingpreview|msnbot|yandex|baiduspider|duckduckbot|duckassistbot|applebot(?!-extended)|slurp|seznambot|petalbot|sogou|naver|yeti|qwantify|mojeek|coccocbot/i';

    private const AI = '/gptbot|chatgpt-user|oai-searchbot|claudebot|claude-web|claude-user|claude-searchbot|anthropic-ai|perplexitybot|perplexity-user|ccbot|bytespider|google-extended|applebot-extended|amazonbot|cohere-ai|meta-externalagent|meta-externalfetcher|facebookbot|diffbot|youbot|timpibot|omgili|ai2bot|imagesiftbot|mistralai|deepseek|kangaroo bot/i';

    private const OTHER = '/bot\b|bot\/|crawl|spider|scrap|curl|wget|python|httpclient|http-client|java\/|go-http|okhttp|axios|node-fetch|undici|libwww|perl|ruby|php\/|guzzle|headless|phantomjs|selenium|puppeteer|playwright|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|facebookexternalhit|whatsapp|telegrambot|slackbot|discordbot|twitterbot|linkedinbot|skypeuripreview|embedly|preview|validator|feed|rss|ahrefs|semrush|mj12|dotbot|bitlybot|archive\.org|ia_archiver|zgrab|masscan|nmap|nuclei|httpx/i';

    /** Null for a person, otherwise search | ai | fake | other. */
    public static function kind(?string $userAgent, ?string $acceptLanguage): ?string
    {
        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return 'other';
        }
        if (preg_match(self::AI, $ua)) {
            return 'ai';
        }
        if (preg_match(self::SEARCH, $ua)) {
            return 'search';
        }
        if (preg_match(self::OTHER, $ua)) {
            return 'other';
        }
        // Real browsers always send the languages they accept; a script dressed as one usually doesn't.
        if (trim((string) $acceptLanguage) === '') {
            return 'fake';
        }
        if (! preg_match('/mozilla\/|opera\//i', $ua)) {
            return 'other';
        }

        return null;
    }
}

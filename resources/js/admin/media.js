/**
 * Image field used by the product form (many images) and the category form (one image).
 * Uploads go straight to the media library; the form only submits the chosen ids.
 */
const MAX_BYTES = 10 * 1024 * 1024;
const TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function mediaMixin({ media = [], multiple = true, uploadUrl, libraryUrl, messages = {} }) {
    return {
        media: multiple ? media : media.slice(0, 1),
        multiple,
        uploads: [],
        dragging: false,
        library: { open: false, items: [], next: null, loading: false, q: '', selected: [] },

        pickFiles(event) {
            this.queueFiles([...(event.target.files ?? [])]);
            event.target.value = '';
        },

        dropFiles(event) {
            this.dragging = false;
            this.queueFiles([...(event.dataTransfer?.files ?? [])]);
        },

        queueFiles(files) {
            if (!this.multiple) files = files.slice(0, 1);

            files.forEach((file) => {
                const upload = { id: `${Date.now()}-${Math.random()}`, name: file.name, progress: 0, error: null };

                if (!TYPES.includes(file.type)) upload.error = messages.type;
                else if (file.size > MAX_BYTES) upload.error = messages.size;

                this.uploads.push(upload);
                if (!upload.error) this.send(file, upload.id);
            });
        },

        findUpload(id) {
            return this.uploads.find((u) => u.id === id);
        },

        send(file, uploadId) {
            const data = new FormData();
            data.append('file', file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = (e) => {
                const upload = this.findUpload(uploadId);
                if (upload && e.lengthComputable) upload.progress = Math.round((e.loaded / e.total) * 100);
            };

            xhr.onload = () => {
                const upload = this.findUpload(uploadId);
                let body = {};
                try { body = JSON.parse(xhr.responseText); } catch (e) { /* not JSON */ }

                if (xhr.status === 201) {
                    this.addMedia(body);
                    this.uploads = this.uploads.filter((u) => u.id !== uploadId);
                    this.$dispatch('media-uploaded', body);
                } else if (upload) {
                    upload.error = body?.errors?.file?.[0] ?? body?.message ?? messages.failed;
                }
            };

            xhr.onerror = () => {
                const upload = this.findUpload(uploadId);
                if (upload) upload.error = messages.failed;
            };

            xhr.send(data);
        },

        addMedia(item) {
            if (!this.multiple) {
                this.media = [item];
                return;
            }
            if (!this.media.some((m) => m.id === item.id)) this.media.push(item);
        },

        removeMedia(id) {
            this.media = this.media.filter((m) => m.id !== id);
            if (this.variants) this.variants.forEach((v) => { if (Number(v.media_id) === Number(id)) v.media_id = null; });
        },

        moveMedia(id, position) {
            const index = this.media.findIndex((m) => m.id === id);
            if (index < 0) return;
            const [item] = this.media.splice(index, 1);
            this.media.splice(position, 0, item);
        },

        shiftMedia(index, by) {
            const target = index + by;
            if (target < 0 || target >= this.media.length) return;
            this.moveMedia(this.media[index].id, target);
        },

        dismissUpload(id) {
            this.uploads = this.uploads.filter((u) => u.id !== id);
        },

        async openLibrary() {
            this.library.open = true;
            this.library.selected = [];
            if (!this.library.items.length) await this.loadLibrary(true);
        },

        async loadLibrary(reset = false) {
            this.library.loading = true;
            const url = reset
                ? `${libraryUrl}&q=${encodeURIComponent(this.library.q)}`
                : this.library.next;

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const body = await response.json();
                this.library.items = reset ? body.data : [...this.library.items, ...body.data];
                this.library.next = body.next;
            } finally {
                this.library.loading = false;
            }
        },

        toggleLibraryItem(item) {
            if (!this.multiple) {
                this.library.selected = [item];
                return;
            }
            const i = this.library.selected.findIndex((s) => s.id === item.id);
            if (i >= 0) this.library.selected.splice(i, 1);
            else this.library.selected.push(item);
        },

        isSelectedInLibrary(item) {
            return this.library.selected.some((s) => s.id === item.id);
        },

        addFromLibrary() {
            this.library.selected.forEach((item) => this.addMedia(item));
            this.library.open = false;
        },
    };
}

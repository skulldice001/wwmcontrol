<div class="form-group">
    <label>{{ __('messages.lib_title') }} <span class="text-danger">*</span></label>
    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $article->title ?? '') }}" required maxlength="255">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>{{ __('messages.lib_category') }} <span class="text-danger">*</span></label>
    <select name="category" class="form-control @error('category') is-invalid @enderror" required>
        @foreach(\App\Models\LibraryArticle::CATEGORIES as $key => $label)
        <option value="{{ $key }}" {{ old('category', $article->category ?? '') === $key ? 'selected' : '' }}>
            {{ $label }}
        </option>
        @endforeach
    </select>
    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>{{ __('messages.lib_excerpt') }} <small class="text-muted">({{ __('messages.lib_excerpt_hint') }})</small></label>
    <input type="text" name="excerpt" class="form-control @error('excerpt') is-invalid @enderror"
           value="{{ old('excerpt', $article->excerpt ?? '') }}" maxlength="500"
           placeholder="{{ __('messages.lib_excerpt_placeholder') }}">
    @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>{{ __('messages.lib_content') }} <span class="text-danger">*</span></label>
    @php $fmt = old('content_format', $article->content_format ?? 'markdown'); @endphp
    <input type="hidden" name="content_format" id="contentFormat" value="{{ $fmt }}">

    @if($fmt === 'markdown')
        {{-- EasyMDE WYSIWYG Markdown editor --}}
        <textarea id="articleContent" name="content"
                  class="@error('content') is-invalid @enderror">{{ old('content', $article->content ?? '') }}</textarea>
    @else
        {{-- Raw textarea for html/plain legacy articles --}}
        <div class="mb-1 d-flex align-items-center" style="gap:8px;">
            <button type="button" class="btn btn-sm btn-outline-info" id="insertImgBtn">
                <i class="fas fa-image mr-1"></i> {{ __('messages.lib_insert_image') }}
            </button>
            <input type="file" id="imgFileInput" accept="image/*" class="d-none">
            <span id="imgUploadStatus" class="text-muted" style="font-size:12px;"></span>
        </div>
        <textarea id="articleContent" name="content" rows="22"
                  class="form-control @error('content') is-invalid @enderror"
                  style="font-family:monospace;font-size:13px;">{{ old('content', $article->content ?? '') }}</textarea>
        <small class="form-text text-muted">{{ __('messages.lib_html_mode_hint') }}</small>
    @endif
    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

@if($fmt === 'markdown')
<script>
(function waitForEasyMDE() {
    if (typeof EasyMDE === 'undefined') {
        return setTimeout(waitForEasyMDE, 50);
    }

    const editor = new EasyMDE({
        element: document.getElementById('articleContent'),
        spellChecker: false,
        autosave: { enabled: false },
        uploadImage: true,
        imageUploadFunction: function(file, onSuccess, onError) {
            const form = new FormData();
            form.append('image', file);
            form.append('_token', '{{ csrf_token() }}');
            fetch('{{ route('admin.library.upload-image') }}', { method: 'POST', body: form })
                .then(r => r.json())
                .then(data => { if (data.url) onSuccess(data.url); else onError('{{ __('messages.lib_upload_failed') }}'); })
                .catch(() => onError('{{ __('messages.lib_upload_error') }}'));
        },
        toolbar: [
            'bold', 'italic', 'heading', '|',
            'quote', 'unordered-list', 'ordered-list', '|',
            'link', 'upload-image', '|',
            'preview', 'side-by-side', 'fullscreen', '|',
            'guide',
        ],
        minHeight: '420px',
        placeholder: '{{ __('messages.lib_content') }}...',
    });
})();
</script>
@else
<script>
(function () {
    const btn    = document.getElementById('insertImgBtn');
    const input  = document.getElementById('imgFileInput');
    const status = document.getElementById('imgUploadStatus');
    const ta     = document.getElementById('articleContent');

    btn.addEventListener('click', () => input.click());

    input.addEventListener('change', async () => {
        const file = input.files[0];
        if (!file) return;

        status.textContent = '{{ __('messages.lib_uploading') }}';
        btn.disabled = true;

        const form = new FormData();
        form.append('image', file);
        form.append('_token', '{{ csrf_token() }}');

        try {
            const res  = await fetch('{{ route('admin.library.upload-image') }}', { method: 'POST', body: form });
            const data = await res.json();

            if (data.url) {
                const tag   = `<img src="${data.url}" style="max-width:100%;border-radius:6px;margin:8px 0;" alt="">`;
                const start = ta.selectionStart;
                const end   = ta.selectionEnd;
                ta.value    = ta.value.slice(0, start) + tag + ta.value.slice(end);
                ta.selectionStart = ta.selectionEnd = start + tag.length;
                ta.focus();
                status.textContent = '{{ __('messages.lib_inserted') }}';
            } else {
                status.textContent = '{{ __('messages.lib_upload_failed') }}';
            }
        } catch (e) {
            status.textContent = '{{ __('messages.lib_upload_error') }}';
        } finally {
            btn.disabled = false;
            input.value  = '';
            setTimeout(() => status.textContent = '', 3000);
        }
    });
})();
</script>
@endif

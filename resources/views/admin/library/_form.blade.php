<div class="form-group">
    <label>Tiêu đề <span class="text-danger">*</span></label>
    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $article->title ?? '') }}" required maxlength="255">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>Danh mục <span class="text-danger">*</span></label>
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
    <label>Tóm tắt <small class="text-muted">(tùy chọn, hiển thị trên thẻ bài)</small></label>
    <input type="text" name="excerpt" class="form-control @error('excerpt') is-invalid @enderror"
           value="{{ old('excerpt', $article->excerpt ?? '') }}" maxlength="500"
           placeholder="Tóm tắt ngắn...">
    @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>Nội dung <span class="text-danger">*</span></label>

    {{-- Image insert toolbar --}}
    <div class="mb-1 d-flex align-items-center gap-2" style="gap:8px;">
        <button type="button" class="btn btn-sm btn-outline-info" id="insertImgBtn">
            <i class="fas fa-image mr-1"></i> Chèn ảnh
        </button>
        <input type="file" id="imgFileInput" accept="image/*" class="d-none">
        <span id="imgUploadStatus" class="text-muted" style="font-size:12px;"></span>
    </div>

    <textarea id="articleContent" name="content" rows="18"
              class="form-control @error('content') is-invalid @enderror"
              required style="font-family:monospace;font-size:13px;">{{ old('content', $article->content ?? '') }}</textarea>
    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="form-text text-muted">Hỗ trợ HTML. Dòng trống = ngăn cách đoạn văn.</small>
</div>

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

        status.textContent = 'Đang tải lên...';
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
                status.textContent = 'Đã chèn ảnh ✓';
            } else {
                status.textContent = 'Lỗi: ' + (data.message || 'upload thất bại');
            }
        } catch (e) {
            status.textContent = 'Lỗi kết nối.';
        } finally {
            btn.disabled = false;
            input.value  = '';
            setTimeout(() => status.textContent = '', 3000);
        }
    });
})();
</script>

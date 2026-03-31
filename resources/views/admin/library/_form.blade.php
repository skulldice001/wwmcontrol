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
    <textarea name="content" rows="18" class="form-control @error('content') is-invalid @enderror"
              required style="font-family:monospace;font-size:13px;">{{ old('content', $article->content ?? '') }}</textarea>
    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="form-text text-muted">Hỗ trợ xuống dòng tự do. Dòng trống = ngăn cách đoạn văn.</small>
</div>

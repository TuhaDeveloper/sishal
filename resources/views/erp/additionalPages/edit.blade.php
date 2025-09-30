@extends('erp.master')

@section('title','Edit Additional Page')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-0">Edit Additional Page</h2>
                </div>
            </div>
        </div>
        <div class="container-fluid px-4 py-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('additionalPages.update',$page->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" value="{{ old('title',$page->title) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" value="{{ old('slug',$page->slug) }}" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Content</label>
                                <input type="hidden" name="content" id="content_input" value="{{ old('content',$page->content) }}">
                                <div id="quill_editor_edit" style="height: 300px; background: #fff;" class="border rounded"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Position</label>
                                <select name="positioned_at" class="form-select">
                                    <option value="navbar" {{ old('positioned_at',$page->positioned_at)==='navbar' ? 'selected' : '' }}>Navbar</option>
                                    <option value="footer" {{ old('positioned_at',$page->positioned_at)==='footer' ? 'selected' : '' }}>Footer</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ $page->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 d-flex gap-2">
                            <a href="{{ route('additionalPages.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quill assets -->
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <script>
        (function(){
            var form = document.querySelector('form[action="{{ route('additionalPages.update',$page->id) }}"]');
            var titleInput = document.querySelector('input[name="title"]');
            var slugInput = document.querySelector('input[name="slug"]');
            var originalSlug = slugInput ? slugInput.value : '';
            var slugTouched = false;
            if (slugInput) {
                slugInput.addEventListener('input', function(){ slugTouched = true; });
            }
            function slugify(str){
                return (str || '')
                    .toString()
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }
            if (titleInput && slugInput) {
                titleInput.addEventListener('input', function(){
                    if (!slugTouched || !slugInput.value || slugInput.value === originalSlug) {
                        slugInput.value = slugify(titleInput.value);
                    }
                });
            }
            var quill = new Quill('#quill_editor_edit', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'align': [] }],
                        ['link', 'blockquote', 'code-block', 'image'],
                        ['clean']
                    ]
                }
            });
            // Set initial content from hidden input
            var initial = document.getElementById('content_input').value || '';
            if (initial) {
                document.querySelector('#quill_editor_edit .ql-editor').innerHTML = initial;
            }
            form.addEventListener('submit', function(){
                document.getElementById('content_input').value = quill.root.innerHTML;
            });
        })();
    </script>
@endsection


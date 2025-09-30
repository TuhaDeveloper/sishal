@extends('erp.master')

@section('title', 'Invoice Management')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <!-- Header Section -->
        <div class="container-fluid px-4 py-3 bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('erp.dashboard') }}"
                                    class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Invoice Template List</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Invoice Template List</h2>
                    <p class="text-muted mb-0">Manage invoice template information, contacts, and transactions efficiently.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2">
                        <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                            <i class="fas fa-plus me-2"></i>Add Template
                        </a>
                        <button class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="container-fluid px-4 py-4">
            <div class="mb-3">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search (Template Name)</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Template Name">
                    </div>
                    <div class="col-md-1 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                        <a href="{{ route('invoice.template.list') }}" class="btn btn-outline-danger">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Template List</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="border-0">ID</th>
                                    <th class="border-0">Name</th>
                                    <th class="border-0">Footer Note</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $template->name }}</td>
                                        <td>{!! $template->footer_note !!}</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $template->is_default ? 'Default' : 'Normal' }}
                                            </span>
                                        </td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editTemplateModal{{ $template->id }}">
                                                Edit
                                            </button>
                                            <!-- Delete Form -->
                                            <form action="{{ route('invoice.template.delete', $template->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editTemplateModal{{ $template->id }}" tabindex="-1" aria-labelledby="editTemplateModalLabel{{ $template->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('invoice.template.update', $template->id) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editTemplateModalLabel{{ $template->id }}">Edit Invoice Template</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="editTemplateName{{ $template->id }}" class="form-label">Template Name <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" id="editTemplateName{{ $template->id }}" name="name" value="{{ $template->name }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="editFooterNoteEditor{{ $template->id }}" class="form-label">Footer Note</label>
                                                                    <div id="editFooterNoteEditor{{ $template->id }}" style="height: 120px;">{!! $template->footer_note !!}</div>
                                                                    <input type="hidden" name="footer_note" id="editFooterNoteInput{{ $template->id }}" value="{{ $template->footer_note }}">
                                                                </div>
                                                                <div class="form-check mb-3">
                                                                    <input class="form-check-input" type="checkbox" value="1" id="editIsDefault{{ $template->id }}" name="is_default" {{ $template->is_default ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="editIsDefault{{ $template->id }}">
                                                                        Default Template
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update Template</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                              document.addEventListener('DOMContentLoaded', function () {
                                                var quillEdit{{ $template->id }} = new Quill('#editFooterNoteEditor{{ $template->id }}', {
                                                  theme: 'snow',
                                                  modules: { toolbar: [
                                                    [{ header: [1, 2, false] }],
                                                    ['bold', 'italic', 'underline'],
                                                    ['link', 'clean']
                                                  ] }
                                                });
                                                // Set initial content if any
                                                var initialContentEdit = document.getElementById('editFooterNoteInput{{ $template->id }}').value;
                                                if (initialContentEdit) {
                                                  quillEdit{{ $template->id }}.root.innerHTML = initialContentEdit;
                                                }
                                                // On form submit, update hidden input with HTML
                                                var formEdit = document.querySelector('#editTemplateModal{{ $template->id }} form');
                                                formEdit.addEventListener('submit', function () {
                                                  document.getElementById('editFooterNoteInput{{ $template->id }}').value = quillEdit{{ $template->id }}.root.innerHTML;
                                                });
                                              });
                                            </script>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No templates found for the given criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">
                            Showing {{ $templates->firstItem() }} to {{ $templates->lastItem() }} of {{ $templates->total() }} templates
                        </span>
                        {{ $templates->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('invoice.template.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="addTemplateModalLabel">Add Invoice Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          <div class="mb-3">
            <label for="templateName" class="form-label">Template Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="templateName" name="name" value="{{ old('name') }}" required>
          </div>
          <div class="mb-3">
            <label for="footerNote" class="form-label">Footer Note</label>
            <div id="footerNoteEditor" style="height: 120px;">{!! old('footer_note') !!}</div>
            <input type="hidden" name="footer_note" id="footerNoteInput" value="{{ old('footer_note') }}">
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="isDefault" name="is_default" {{ old('is_default') ? 'checked' : '' }}>
            <label class="form-check-label" for="isDefault">
              Default Template
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Template</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Quill Editor CDN -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var quill = new Quill('#footerNoteEditor', {
      theme: 'snow',
      modules: { toolbar: [
        [{ header: [1, 2, false] }],
        ['bold', 'italic', 'underline'],
        ['link', 'clean']
      ] }
    });
    // Set initial content if any
    var initialContent = document.getElementById('footerNoteInput').value;
    if (initialContent) {
      quill.root.innerHTML = initialContent;
    }
    // On form submit, update hidden input with HTML
    var form = document.querySelector('#addTemplateModal form');
    form.addEventListener('submit', function () {
      document.getElementById('footerNoteInput').value = quill.root.innerHTML;
    });
  });
</script>
@endsection
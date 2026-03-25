{{--
    Shared notification utilities:
    - notify(type, message)        → Bootstrap 4 toast (type: success|error|warning|info)
    - confirmDialog(msg, onConfirm) → Bootstrap 4 modal confirm (replaces confirm())
    Include once per page via @include('partials.notify')
--}}

{{-- Toast container (bottom-right) --}}
<div style="position:fixed; bottom:1.25rem; right:1.25rem; z-index:99999;" aria-live="polite" aria-atomic="true">
    <div id="app-toast" class="toast shadow" role="alert" aria-live="assertive"
         data-autohide="true" data-delay="4500" style="min-width:300px; max-width:380px;">
        <div class="toast-header" id="toast-header">
            <i class="mr-2" id="toast-icon" style="font-size:13px;"></i>
            <strong class="mr-auto" id="toast-title"></strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body" id="toast-body" style="word-break:break-word;"></div>
    </div>
</div>

{{-- Confirm modal (replaces browser confirm()) --}}
<div class="modal fade" id="confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title font-weight-bold" id="confirm-modal-title">
                    {{ __('messages.confirm') }}
                </h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body px-3 py-3" id="confirm-modal-body"></div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-danger btn-sm font-weight-bold" id="confirm-ok-btn">
                    {{ __('messages.confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── notify(type, message) ─────────────────────────────────────────────────
const _NOTIFY = {
    success : { hdrCls: 'bg-success text-white', icon: 'fas fa-check-circle',         label: '{{ __("messages.poker_notify_success") }}' },
    error   : { hdrCls: 'bg-danger  text-white', icon: 'fas fa-times-circle',         label: '{{ __("messages.poker_notify_error") }}' },
    warning : { hdrCls: 'bg-warning text-dark',  icon: 'fas fa-exclamation-triangle', label: '{{ __("messages.poker_notify_warning") }}' },
    info    : { hdrCls: 'bg-info    text-white', icon: 'fas fa-info-circle',           label: '{{ __("messages.poker_notify_info") }}' },
};

window.notify = function (type, message) {
    const cfg = _NOTIFY[type] || _NOTIFY.info;
    $('#toast-header').attr('class', 'toast-header ' + cfg.hdrCls);
    $('#toast-icon').attr('class', cfg.icon + ' mr-2');
    $('#toast-title').text(cfg.label);
    $('#toast-body').text(message);
    $('#app-toast').toast('show');
};

// ── confirmDialog(message, onConfirm) ─────────────────────────────────────
window.confirmDialog = function (message, onConfirm) {
    $('#confirm-modal-body').text(message);
    $('#confirm-ok-btn').off('click').on('click', function () {
        $('#confirm-modal').modal('hide');
        onConfirm();
    });
    $('#confirm-modal').modal('show');
};
</script>

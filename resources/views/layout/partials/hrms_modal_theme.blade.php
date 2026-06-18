<style>
    :root {
        --hrms-theme: #7c5cc4;
        --hrms-theme-dark: #5f459f;
        --hrms-theme-soft: rgba(124, 92, 196, 0.1);
        --hrms-theme-softer: rgba(124, 92, 196, 0.06);
        --hrms-danger: #dc3545;
        --hrms-danger-dark: #b02a37;
    }

    .modal-backdrop.show {
        opacity: 0.42;
    }

    /* ── Standard form / view modals ── */
    .modal:not(.hrms-leave-info-modal) .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(124, 92, 196, 0.16);
    }

    .modal:not(.hrms-leave-info-modal) .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, var(--hrms-theme) 0%, var(--hrms-theme-dark) 100%);
        color: #fff;
        border-bottom: 0;
        padding: 18px 24px;
    }

    .modal:not(.hrms-leave-info-modal) .modal-header .modal-title,
    .modal:not(.hrms-leave-info-modal) .modal-header h2,
    .modal:not(.hrms-leave-info-modal) .modal-header h4,
    .modal:not(.hrms-leave-info-modal) .modal-header h5 {
        color: #fff;
        font-weight: 700;
        margin: 0;
        font-size: 1.15rem;
    }

    .modal:not(.hrms-leave-info-modal) .modal-header .close {
        color: #fff;
        opacity: 0.92;
        text-shadow: none;
        margin: 0;
        padding: 0;
        font-size: 1.5rem;
        line-height: 1;
    }

    .modal:not(.hrms-leave-info-modal) .modal-header .close i,
    .modal:not(.hrms-leave-info-modal) .modal-header .close span {
        color: #fff;
    }

    .modal:not(.hrms-leave-info-modal) .modal-body {
        padding: 22px 24px;
    }

    .modal:not(.hrms-leave-info-modal) .modal-body label {
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        margin-bottom: 6px;
    }

    .modal:not(.hrms-leave-info-modal) .modal-body .form-control,
    .modal:not(.hrms-leave-info-modal) .modal-body .bootstrap-select > .dropdown-toggle {
        border-radius: 8px;
        border-color: #e5e7eb;
        min-height: 40px;
    }

    .modal:not(.hrms-leave-info-modal) .modal-body .form-control:focus,
    .modal:not(.hrms-leave-info-modal) .modal-body select.form-control:focus {
        border-color: var(--hrms-theme);
        box-shadow: 0 0 0 0.2rem rgba(124, 92, 196, 0.18);
    }

    .modal:not(.hrms-leave-info-modal) .modal-body textarea.form-control {
        min-height: 88px;
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer {
        border-top: 1px solid rgba(124, 92, 196, 0.12);
        background: var(--hrms-theme-softer);
        padding: 14px 24px;
        justify-content: flex-end;
        gap: 8px;
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer .btn,
    .modal:not(.hrms-leave-info-modal) .modal-body .btn {
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 20px;
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer .btn-default,
    .modal:not(.hrms-leave-info-modal) .modal-footer .btn-light,
    .modal:not(.hrms-leave-info-modal) .modal-footer button.close.btn-default,
    .modal:not(.hrms-leave-info-modal) .modal-footer button[data-dismiss="modal"]:not(.btn-danger):not(.btn-warning):not(.btn-primary):not(.btn-success):not(.ok):not(#ok_button) {
        background: #fff;
        border: 1px solid #d1d5db;
        color: #4b5563;
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer .btn-warning,
    .modal:not(.hrms-leave-info-modal) .modal-body .btn-warning,
    .modal:not(.hrms-leave-info-modal) #action_button {
        background: var(--hrms-theme);
        border-color: var(--hrms-theme);
        color: #fff;
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer .btn-warning:hover,
    .modal:not(.hrms-leave-info-modal) .modal-body .btn-warning:hover,
    .modal:not(.hrms-leave-info-modal) #action_button:hover {
        background: var(--hrms-theme-dark);
        border-color: var(--hrms-theme-dark);
        color: #fff;
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer .btn-primary {
        background: var(--hrms-theme);
        border-color: var(--hrms-theme);
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer .btn-primary:hover {
        background: var(--hrms-theme-dark);
        border-color: var(--hrms-theme-dark);
    }

    .modal:not(.hrms-leave-info-modal) .modal-footer .btn-danger,
    .modal:not(.hrms-leave-info-modal) .modal-body .btn-danger {
        border-radius: 8px;
        font-weight: 600;
    }

    .modal .form_result,
    .modal #form_result,
    .modal [id$="_form_result"] {
        display: block;
        margin-bottom: 16px;
    }

    .modal .alert {
        border-radius: 10px;
        border: 0;
        margin-bottom: 0;
    }

    .modal:not(.hrms-leave-info-modal) .modal-body .table-bordered {
        border-radius: 10px;
        overflow: hidden;
        border-color: rgba(124, 92, 196, 0.12);
        margin-bottom: 0;
    }

    .modal:not(.hrms-leave-info-modal) .modal-body .table-bordered th {
        background: var(--hrms-theme-softer);
        color: #5f459f;
        font-weight: 600;
        width: 38%;
        vertical-align: middle;
    }

    .modal:not(.hrms-leave-info-modal) .modal-body .table-bordered td {
        vertical-align: middle;
        color: #374151;
    }

    /* ── Delete / confirmation modals ── */
    .modal.confirmModal .modal-header,
    #confirmModal .modal-header {
        background: linear-gradient(135deg, var(--hrms-danger) 0%, var(--hrms-danger-dark) 100%);
    }

    .modal.confirmModal .modal-body,
    #confirmModal .modal-body {
        text-align: center;
        padding: 28px 24px;
    }

    .modal.confirmModal .modal-body h4,
    #confirmModal .modal-body h4 {
        color: #374151;
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.65;
        margin: 0;
    }

    /* ── Modal submit loading overlay ── */
    .modal.hrms-modal-loading .modal-content {
        position: relative;
    }

    .modal.hrms-modal-loading .modal-content::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.78);
        z-index: 20;
        border-radius: inherit;
        pointer-events: all;
    }

    .modal.hrms-modal-loading .modal-content::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 42px;
        height: 42px;
        margin: -21px 0 0 -21px;
        border: 4px solid #ddd;
        border-top-color: var(--hrms-theme);
        border-radius: 50%;
        animation: hrms-modal-spin 0.8s linear infinite;
        z-index: 21;
    }

    @keyframes hrms-modal-spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* ── Leave / WFH info modal ── */
    .hrms-leave-info-modal {
        --hrms-theme: #7c5cc4;
        --hrms-theme-dark: #5f459f;
        --hrms-theme-soft: rgba(124, 92, 196, 0.1);
        --hrms-theme-softer: rgba(124, 92, 196, 0.06);
    }

    .hrms-leave-info-modal .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(124, 92, 196, 0.18);
    }

    .hrms-leave-info-modal .hrms-info-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        background: linear-gradient(135deg, var(--hrms-theme) 0%, var(--hrms-theme-dark) 100%);
        color: #fff;
        border: 0;
    }

    .hrms-leave-info-modal .hrms-info-eyebrow {
        display: block;
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.85;
        margin-bottom: 4px;
    }

    .hrms-leave-info-modal .hrms-info-header .modal-title {
        color: #fff;
        font-size: 1.35rem;
        font-weight: 700;
    }

    .hrms-leave-info-modal .hrms-info-close {
        color: #fff;
        opacity: 0.9;
        text-shadow: none;
        font-size: 1.75rem;
        margin: 0;
        padding: 0;
    }

    .hrms-leave-info-modal .hrms-info-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 24px;
        background: var(--hrms-theme-softer);
        border-bottom: 1px solid rgba(124, 92, 196, 0.15);
    }

    .hrms-leave-info-modal .hrms-info-person {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .hrms-leave-info-modal .hrms-info-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--hrms-theme), #9b7fd4);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 15px;
        flex-shrink: 0;
    }

    .hrms-leave-info-modal .hrms-info-name {
        margin: 0 0 4px;
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
    }

    .hrms-leave-info-modal .hrms-info-meta {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .hrms-leave-info-modal .hrms-info-dot {
        margin: 0 6px;
    }

    .hrms-leave-info-modal .hrms-status-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .hrms-leave-info-modal .hrms-status-badge.hrms-badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .hrms-leave-info-modal .hrms-status-badge.hrms-badge-approved {
        background: #dcfce7;
        color: #166534;
    }

    .hrms-leave-info-modal .hrms-status-badge.hrms-badge-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .hrms-leave-info-modal .hrms-status-badge.hrms-badge-default {
        background: #f3f4f6;
        color: #374151;
    }

    .hrms-leave-info-modal .hrms-info-section {
        padding: 18px 24px 0;
    }

    .hrms-leave-info-modal .hrms-info-section-title {
        margin: 0 0 12px;
        padding-left: 10px;
        border-left: 3px solid var(--hrms-theme);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #5f459f;
    }

    .hrms-leave-info-modal .hrms-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .hrms-leave-info-modal .hrms-info-card {
        background: var(--hrms-theme-softer);
        border: 1px solid rgba(124, 92, 196, 0.14);
        border-radius: 10px;
        padding: 12px 14px;
    }

    .hrms-leave-info-modal .hrms-info-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .hrms-leave-info-modal .hrms-info-value {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }

    .hrms-leave-info-modal .hrms-info-text-block {
        margin: 0;
        padding: 14px 16px;
        background: var(--hrms-theme-softer);
        border: 1px solid rgba(124, 92, 196, 0.14);
        border-radius: 10px;
        color: #374151;
        line-height: 1.55;
        white-space: pre-wrap;
    }

    .hrms-leave-info-modal .hrms-info-footer-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 18px 24px 22px;
    }

    .hrms-leave-info-modal .hrms-info-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        background: var(--hrms-theme-soft);
        color: var(--hrms-theme-dark);
        font-size: 12px;
        font-weight: 600;
    }

    .hrms-leave-info-modal .hrms-info-modal-footer {
        border-top: 1px solid rgba(124, 92, 196, 0.15);
        background: var(--hrms-theme-softer);
        padding: 14px 24px;
    }

    .hrms-leave-info-modal .hrms-info-close-btn {
        background: var(--hrms-theme);
        border-color: var(--hrms-theme);
        color: #fff;
        font-weight: 600;
        padding: 8px 22px;
        border-radius: 8px;
    }

    .hrms-leave-info-modal .hrms-info-close-btn:hover,
    .hrms-leave-info-modal .hrms-info-close-btn:focus {
        background: var(--hrms-theme-dark);
        border-color: var(--hrms-theme-dark);
        color: #fff;
    }

    @media (max-width: 575px) {
        .hrms-leave-info-modal .hrms-info-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .hrms-leave-info-modal .hrms-info-grid {
            grid-template-columns: 1fr;
        }

        .modal:not(.hrms-leave-info-modal) .modal-body,
        .modal:not(.hrms-leave-info-modal) .modal-header,
        .modal:not(.hrms-leave-info-modal) .modal-footer {
            padding-left: 16px;
            padding-right: 16px;
        }
    }
</style>

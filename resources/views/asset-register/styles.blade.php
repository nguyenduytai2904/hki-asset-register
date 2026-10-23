<style>
    .hk-main-content:has(.ar-page) { height: calc(100vh - var(--hk-header-height)); min-height: 0; overflow: hidden; display: flex; flex-direction: column; }
    .ar-page { display: flex; flex: 1 1 0; flex-direction: column; width: 100%; min-width: 0; min-height: 0; margin: 0; padding: 0; overflow: hidden; color: #343b49; }
    .ar-tabs { display: flex; flex: 0 0 auto; gap: 24px; border-bottom: 1px solid #e0e3e8; margin-bottom: 16px; overflow-x: auto; }
    .ar-tab { display: inline-flex; align-items: center; gap: 5px; color: #737985; text-decoration: none; padding: 8px 3px 10px; white-space: nowrap; }
    .ar-tab .material-symbols-rounded { font-size: 15px; }
    .ar-tab.is-active { color: #343b49; border-bottom: 2px solid #343b49; font-weight: 600; }
    .ar-heading { flex: 0 0 auto; margin: 0 0 14px; }
    .ar-page-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
    .ar-heading-actions { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }
    .ar-preview-label { color: #818792; font-size: 11px; white-space: nowrap; }
    .ar-heading h1, .ar-panel h1 { font-size: 20px; font-weight: 650; margin: 0 0 4px; }
    .ar-heading p, .ar-panel p { color: #7d828b; font-size: 13px; margin: 0; }
    .ar-card, .ar-panel { background: #fff; border: 1px solid #e4e6ea; border-radius: 7px; margin: 0 0 14px; }
    .ar-card-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 15px; }
    .ar-card-heading h2 { font-size: 15px; font-weight: 600; margin: 0; }
    .ar-table-wrap { overflow-x: auto; }
    .ar-table-wrap table { border-collapse: collapse; width: 100%; font-size: 13px; }
    .ar-table-wrap th, .ar-table-wrap td { border-top: 1px solid #eceef1; padding: 9px 12px; text-align: left; white-space: nowrap; }
    .ar-table-wrap th { color: #777d87; font-size: 12px; font-weight: 500; }
    .ar-icon { font-size: 17px; vertical-align: middle; margin-right: 8px; color: #424957; }
    .ar-muted { color: #8a8e96; }
    .ar-status { display: inline-block; border-radius: 20px; background: #e4f4ee; color: #17805f; padding: 3px 9px; font-size: 11px; }
    .ar-status.is-inactive { background: #eeeeef; color: #777; }
    .ar-status.is-pending { background: #fff2df; color: #bd6c00; }
    .ar-status.is-warning { background: #fff0dc; color: #bc6b00; }
    .ar-status.is-danger { background: #fde9e9; color: #bd3a3a; }
    .ar-button { display: inline-flex; align-items: center; border: 1px solid #d8dbe0; border-radius: 5px; background: #fff; color: #343b49; padding: 7px 10px; font: inherit; font-size: 12px; cursor: pointer; text-decoration: none; }
    .ar-button .material-symbols-rounded { color: currentColor; font-size: 16px; margin-right: 5px; }
    .ar-button-dark { border-color: #343b49; background: #343b49; color: #fff; }
    .ar-button:disabled, .ar-actions button:disabled { opacity: .76; cursor: not-allowed; }
    .ar-button-danger { border-color: #c73636; color: #b52f2f; }
    .ar-actions > details > summary { list-style: none; cursor: pointer; }
    .ar-actions summary::-webkit-details-marker { display: none; }
    .ar-action-menu { position: absolute; z-index: 5; right: 0; top: calc(100% + 5px); width: min(340px, calc(100vw - 40px)); padding: 14px; background: #fff; border: 1px solid #dfe2e6; border-radius: 7px; box-shadow: 0 8px 24px #1d29391a; }
    .ar-dialog { --ar-dialog-top: clamp(12px, 5vh, 40px); position: fixed; inset: var(--ar-dialog-top) 0 auto; width: min(500px, calc(100vw - 32px)); max-height: min(calc(100vh - var(--ar-dialog-top) - 12px), 850px); margin: 0 auto; padding: 0; overflow: auto; border: 1px solid var(--hk-border, #dfe2e6); border-radius: var(--hk-radius-modal, 7px); background: var(--hk-bg-surface, #fff); color: var(--hk-text-primary, #343b49); box-shadow: 0 8px 24px rgb(29 41 57 / 12%); font-family: inherit; }
    .ar-dialog::backdrop { background: rgb(0 0 0 / 30%); }
    .ar-dialog-header { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 17px; border-bottom: 1px solid var(--hk-border, #eceef1); background: var(--hk-bg-surface, #fff); }
    .ar-dialog-header h2 { margin: 0; color: var(--hk-text-primary, #343b49); font-size: var(--hk-font-size-md, 16px); font-weight: 600; }
    .ar-dialog-close { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border: 0; border-radius: 5px; background: transparent; color: var(--hk-text-secondary, #727781); cursor: pointer; }
    .ar-dialog-close:hover { background: #f0f1f2; }
    .ar-dialog-close .material-symbols-rounded { font-size: 20px; }
    .ar-dialog .ar-dialog-form { position: static; width: auto; padding: 16px 17px; border: 0; border-radius: 0; box-shadow: none; }
    .ar-dialog-form label { display: block; margin: 0 0 12px; color: var(--hk-text-secondary, #626873); font-size: var(--hk-font-size-sm, 12px); }
    .ar-dialog-form input, .ar-dialog-form select, .ar-dialog-form textarea { display: block; width: 100%; min-height: 36px; margin-top: 5px; padding: 7px 9px; border: 1px solid var(--hk-border, #d8dbe0); border-radius: var(--hk-radius-sm, 5px); background: var(--hk-bg-surface, #fff); color: var(--hk-text-primary, #343b49); font: inherit; font-size: 13px; }
    .ar-dialog-footer { display: flex; justify-content: flex-end; gap: 8px; padding-top: 5px; }
    .ar-asset-dialog { width: min(760px, calc(100vw - 32px)); }
    .ar-asset-dialog-content { padding: 0; }
    .ar-asset-dialog .ar-asset-form { margin: 0; border: 0; border-radius: 0; box-shadow: none; }
    .ar-asset-dialog .ar-form-section { padding: 14px 17px; }
    .ar-asset-dialog .ar-form-footer { position: sticky; bottom: 0; z-index: 1; border-top: 1px solid #eceef1; background: #fff; }
    .ar-dialog-feedback, .ar-registry-feedback { margin: 10px 17px 16px; padding: 10px 12px; border-radius: 6px; background: #fbeaea; color: #992c2c; font-size: 12px; }
    .ar-dialog-feedback ul { margin: 0; padding-left: 18px; }
    .ar-registry-results { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; overflow: hidden; }
    .ar-registry-results[aria-busy="true"] { opacity: .62; pointer-events: none; }
    .ar-registry-table { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; margin-bottom: 0; }
    .ar-registry-table .ar-table-wrap { flex: 1 1 auto; min-height: 0; overflow: auto; }
    :is(.ar-registry-table .ar-table-wrap, .ar-registry-cards, .ar-mine-list .ar-table-wrap, .ar-settings-card .ar-table-wrap, .ar-pending-card, .ar-asset-form, .ar-detail-main, .ar-detail-side) { scrollbar-width: thin; scrollbar-color: transparent transparent; }
    :is(.ar-registry-table .ar-table-wrap, .ar-registry-cards, .ar-mine-list .ar-table-wrap, .ar-settings-card .ar-table-wrap, .ar-pending-card, .ar-asset-form, .ar-detail-main, .ar-detail-side)::-webkit-scrollbar { width: 8px; height: 8px; }
    :is(.ar-registry-table .ar-table-wrap, .ar-registry-cards, .ar-mine-list .ar-table-wrap, .ar-settings-card .ar-table-wrap, .ar-pending-card, .ar-asset-form, .ar-detail-main, .ar-detail-side)::-webkit-scrollbar-track { background: transparent; }
    :is(.ar-registry-table .ar-table-wrap, .ar-registry-cards, .ar-mine-list .ar-table-wrap, .ar-settings-card .ar-table-wrap, .ar-pending-card, .ar-asset-form, .ar-detail-main, .ar-detail-side)::-webkit-scrollbar-thumb { border-radius: 8px; background: transparent; }
    @media (hover: hover) and (pointer: fine) {
        :is(.ar-registry-table .ar-table-wrap, .ar-registry-cards, .ar-mine-list .ar-table-wrap, .ar-settings-card .ar-table-wrap, .ar-pending-card, .ar-asset-form, .ar-detail-main, .ar-detail-side):is(:hover, :focus-within) { scrollbar-color: rgb(120 130 145 / 55%) transparent; }
        :is(.ar-registry-table .ar-table-wrap, .ar-registry-cards, .ar-mine-list .ar-table-wrap, .ar-settings-card .ar-table-wrap, .ar-pending-card, .ar-asset-form, .ar-detail-main, .ar-detail-side):is(:hover, :focus-within)::-webkit-scrollbar-thumb { background: rgb(120 130 145 / 55%); }
    }
    .ar-registry-table thead th { position: sticky; top: 0; z-index: 1; background: var(--hk-bg-surface, #fff); }
    .ar-registry-table tfoot td { border-bottom: 0; }
    .ar-list-footer { display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; gap: 12px; min-height: 44px; padding: 5px 4px 0; color: #858b95; font-size: 11px; }
    .ar-list-footer nav { margin: 0; }
    .ar-list-footer .pagination { margin: 0; }
    .ar-list-footer .hk-pagination-elegant { width: 100%; gap: 12px; }
    .ar-list-footer .hk-pagination-elegant-meta { font-size: var(--hk-font-size-xs, 0.75rem); }
    .ar-list-footer .hk-pagination-elegant-list .page-link { min-width: 28px; height: 28px; padding: 0 8px; font-size: var(--hk-font-size-xs, 0.75rem); }
    .ar-list-footer .hk-pagination-elegant-list .hk-pagination-elegant-nav .material-symbols-rounded { font-size: 17px; }
    .ar-registry-cards { display: none; flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    .ar-view-content { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; overflow: hidden; }
    .ar-actions details:not([open]) > .ar-action-menu { display: none; }
    .ar-form label, .ar-action-menu label { display: block; margin: 0 0 10px; font-size: 12px; color: #626873; }
    .ar-form input, .ar-action-menu input, .ar-action-menu select { display: block; width: 100%; min-height: 36px; margin-top: 4px; border: 1px solid #d8dbe0; border-radius: 5px; padding: 6px 9px; font: inherit; font-size: 13px; color: #343b49; }
    .ar-action-menu { width: min(300px, calc(100vw - 40px)); }
    .ar-action-menu form + form { margin-top: 10px; }
    .ar-actions { position: relative; width: 42px; text-align: center !important; }
    .ar-actions summary { font-size: 20px; line-height: 1; }
    .ar-actions .ar-action-menu { text-align: left; }
    .ar-empty { color: #7d828b; text-align: center !important; padding: 28px !important; }
    .ar-alert { border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 13px; }
    .ar-alert-success { background: #e4f4ee; color: #176b52; }
    .ar-alert-error { background: #fbeaea; color: #992c2c; }
    .ar-panel { padding: 18px; min-height: 140px; }
    .ar-pending-card { flex: 0 1 auto; max-height: 35%; padding: 13px 14px 12px; overflow-y: auto; border: 1px solid #f3d2a9; border-radius: 7px; background: #fff8ed; margin: 0 0 14px; }
    .ar-pending-card h2 { display: flex; align-items: center; gap: 7px; margin: 0 0 9px; color: #b76b0a; font-size: 13px; font-weight: 600; }
    .ar-pending-card h2 .material-symbols-rounded { font-size: 17px; }
    .ar-pending-item { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border: 1px solid #eceef1; border-radius: 5px; background: #fff; }
    .ar-pending-asset { flex: 1 1 230px; min-width: 180px; font-size: 12px; }
    .ar-preview-asset-link { color: inherit; text-decoration: none; }
    .ar-preview-asset-link:hover { text-decoration: underline; }
    .ar-pending-asset small, .ar-asset-cell small, .ar-registry-card-content small { display: block; margin-top: 3px; color: #858b95; font-size: 11px; white-space: normal; }
    .ar-pending-item > p { margin: 0; color: #747a84; font-size: 11px; }
    .ar-preview-actions { display: flex; gap: 6px; flex: 0 0 auto; }
    .ar-mine-list { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; margin-bottom: 0; }
    .ar-mine-list .ar-table-wrap { flex: 1 1 auto; min-height: 0; overflow: auto; }
    .ar-device-icon { display: inline-flex; align-items: center; justify-content: center; width: 27px; height: 27px; border-radius: 5px; background: #f0f1f2; color: #656b73; flex: 0 0 auto; }
    .ar-device-icon .material-symbols-rounded { font-size: 16px; }
    .ar-asset-cell { display: flex; align-items: center; gap: 8px; min-width: 230px; white-space: normal !important; }
    .ar-asset-cell > span:last-child { min-width: 0; }
    .ar-asset-cell small { max-width: 280px; overflow-wrap: anywhere; }
    .ar-type-icon { vertical-align: middle; margin-right: 5px; font-size: 15px; color: #525966; }
    .ar-avatar { display: inline-flex; align-items: center; justify-content: center; width: 19px; height: 19px; margin-right: 5px; border-radius: 50%; background: #eceeed; color: #656a6e; font-size: 9px; vertical-align: middle; }
    .ar-warranty-warning, .ar-warranty-expired { display: block; margin-top: 2px; font-size: 10px; }
    .ar-warranty-warning { color: #c47a00; }
    .ar-warranty-expired { color: #858b95; }
    .ar-registry-toolbar { display: flex; flex: 0 0 auto; align-items: center; gap: 7px; margin: 0 0 10px; }
    .ar-registry-toolbar select, .ar-search { min-height: 32px; border: 1px solid #dfe2e6; border-radius: 5px; background: #fff; color: #4d535d; font: inherit; font-size: 11px; }
    .ar-registry-toolbar select { max-width: 160px; padding: 5px 8px; }
    .ar-registry-toolbar > .ts-wrapper { flex: 0 1 180px; min-width: 145px; }
    .ar-registry-toolbar > .ts-wrapper .ts-control { min-height: 32px; padding: 2px 6px; font-size: 11px; }
    .ar-registry-toolbar > .ts-wrapper .ts-control .item { max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
    .ar-registry-toolbar > .ts-wrapper .ts-control input { min-width: 35px; font-size: 11px; }
    .ar-search { display: flex; align-items: center; gap: 6px; width: min(280px, 100%); padding: 0 8px; }
    .ar-search .material-symbols-rounded { color: #7b8189; font-size: 16px; }
    .ar-search input { width: 100%; border: 0; outline: 0; background: transparent; font: inherit; font-size: 11px; }
    .ar-registry-toolbar :disabled { opacity: .9; cursor: not-allowed; }
    .ar-page-size { margin-left: auto; }
    .ar-registry-table table { table-layout: auto; }
    .ar-registry-table th, .ar-registry-table td { padding: 8px 9px; font-size: 11px; }
    .ar-registry-table th { font-size: 10px; }
    .ar-registry-table .ar-asset-cell { min-width: 245px; }
    .ar-registry-table .ar-asset-cell small { max-width: 250px; }
    .ar-row-arrow { width: 20px; padding-left: 3px !important; padding-right: 4px !important; color: #818791; text-align: center !important; text-decoration: none; }
    .ar-row-arrow .material-symbols-rounded { font-size: 16px; vertical-align: middle; }
    .ar-row-actions { display: flex; align-items: center; justify-content: flex-end; gap: 4px; }
    .ar-button-quiet { padding: 5px; border-color: transparent; }
    .ar-button-quiet .material-symbols-rounded { margin: 0; }
    .ar-registry-table tfoot td, .ar-mine-list tfoot td { color: #858b95; font-size: 10px; }
    .ar-pagination { text-align: right !important; }
    .ar-pagination button { min-width: 24px; height: 24px; border: 0; border-radius: 4px; background: transparent; color: #737985; }
    .ar-pagination button.is-current { background: #343b49; color: #fff; }
    .ar-pagination button:disabled { cursor: default; }
    .ar-registry-cards, .ar-mobile-count { display: none; }
    .ar-actions button { border: 0; background: transparent; color: #727781; font: inherit; font-size: 15px; }
    .ar-settings-page { gap: 0; }
    .ar-settings-card { display: flex; flex: 1 1 0; flex-direction: column; min-height: 0; margin-bottom: 10px; }
    .ar-settings-card .ar-table-wrap { flex: 1 1 auto; min-height: 0; overflow: auto; }
    .ar-settings-card .ar-card-heading { padding: 10px 12px; }
    .ar-settings-card .ar-card-heading h2 { font-size: 13px; }
    .ar-settings-permission-hint { margin: 0 12px 8px; }
    .ar-settings-card .ar-table-wrap th, .ar-settings-card .ar-table-wrap td { padding: 7px 9px; font-size: 11px; }
    .ar-settings-card .ar-table-wrap th { font-size: 10px; }
    .ar-settings-card .ar-icon { font-size: 14px; margin-right: 5px; }
    .ar-field-error { display: block; margin-top: 4px; color: #b52f2f; font-size: 11px; }
    .ar-form-hint { display: block; margin-top: 4px; color: #858b95; font-size: 10px; }
    .ar-required { color: #bd3a3a; }
    .ar-asset-form { min-height: 0; padding: 0; overflow-y: auto; }
    .ar-form-section { padding: 18px 20px; border-bottom: 1px solid #eceef1; }
    .ar-form-section h2, .ar-detail-card h2 { margin: 0 0 14px; font-size: 14px; font-weight: 600; }
    .ar-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 18px; }
    .ar-form-section label { display: block; min-width: 0; color: #626873; font-size: 12px; }
    .ar-form-section input, .ar-form-section select, .ar-form-section textarea { display: block; width: 100%; min-height: 36px; margin-top: 5px; border: 1px solid #d8dbe0; border-radius: 5px; padding: 7px 9px; background: #fff; color: #343b49; font: inherit; font-size: 13px; }
    .ar-form-section input:read-only { background: #f5f6f7; }
    .ar-form-section textarea { resize: vertical; }
    .ar-form-span { grid-column: 1 / -1; }
    .ar-form-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 14px 20px; }
    .ar-registry-toolbar select { flex: 0 0 auto; }
    .ar-registry-toolbar > button { flex: 0 0 auto; }
    .ar-registry-table th a { color: inherit; text-decoration: none; }
    .ar-registry-table th a:hover { color: #343b49; }
    .ar-registry-table .ar-asset-cell a { color: inherit; text-decoration: none; }
    .ar-registry-table .ar-asset-cell a:hover { text-decoration: underline; }
    .ar-inactive-keeper { color: #9297a0; }
    .ar-detail-breadcrumb { display: flex; gap: 7px; margin-bottom: 10px; color: #818791; font-size: 11px; }
    .ar-detail-breadcrumb a { color: inherit; text-decoration: none; }
    .ar-detail-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .ar-back-link { display: inline-flex; color: #737985; text-decoration: none; }
    .ar-back-link .material-symbols-rounded { font-size: 20px; }
    .ar-detail-icon { width: 32px; height: 32px; }
    .ar-detail-icon .material-symbols-rounded { font-size: 19px; }
    .ar-detail-title { min-width: 0; flex: 1; }
    .ar-detail-title h1 { margin: 0 0 3px; font-size: 18px; font-weight: 650; }
    .ar-detail-title p { margin: 0; color: #858b95; font-size: 11px; }
    .ar-detail-actions { display: flex; gap: 6px; margin-left: auto; }
    .ar-disposal-banner { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; padding: 10px 12px; border-radius: 5px; background: #fbeaea; color: #992c2c; font-size: 12px; }
    .ar-disposal-banner .material-symbols-rounded { font-size: 16px; }
    .ar-detail-grid { display: grid; flex: 1 1 auto; min-height: 0; grid-template-columns: minmax(0, 1fr) minmax(230px, 0.45fr); align-items: stretch; gap: 14px; overflow: hidden; }
    .ar-detail-main, .ar-detail-side { min-height: 0; overflow-y: auto; }
    .ar-detail-card { padding: 14px; }
    .ar-detail-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 18px; margin: 0; }
    .ar-detail-fields div { min-width: 0; }
    .ar-detail-fields dt { margin-bottom: 4px; color: #858b95; font-size: 10px; }
    .ar-detail-fields dd { margin: 0; font-size: 12px; overflow-wrap: anywhere; }
    .ar-detail-field-wide { grid-column: 1 / -1; }
    .ar-detail-card > p { margin: 0; font-size: 12px; }
    .ar-detail-card > h2 { margin-bottom: 12px; }
    .ar-history-row, .ar-handover-row { display: flex; align-items: flex-start; gap: 8px; padding: 9px 0; border-bottom: 1px solid #eceef1; font-size: 12px; }
    .ar-history-row:last-child, .ar-handover-row:last-child { border-bottom: 0; }
    .ar-history-icon { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; flex: 0 0 auto; border-radius: 50%; background: #f0f1f2; color: #727781; font-size: 14px; }
    .ar-history-row small, .ar-handover-row small { display: block; margin-top: 3px; color: #858b95; font-size: 10px; }
    .ar-history-changes { margin: 5px 0 0; padding-left: 16px; color: #626873; font-size: 11px; }
    .ar-pending-note { margin-top: 10px; padding: 9px; border-radius: 5px; background: #fff2df; color: #a65e00; font-size: 10px; }
    .ar-detail-side .ar-detail-card + .ar-detail-card { margin-top: 12px; }
    .ar-registry-cards .pagination { margin: 8px 0; }
    .ar-action-dialog { width: min(540px, calc(100vw - 32px)); }
    .ar-action-form { padding: 15px 18px 17px; }
    .ar-action-form label, .ar-action-fieldset legend { display: block; margin: 0; color: #626873; font-size: 12px; font-weight: 500; }
    .ar-action-form input:not([type="radio"]), .ar-action-form select, .ar-action-form textarea { display: block; width: 100%; min-height: 36px; margin-top: 5px; padding: 7px 9px; border: 1px solid #d8dbe0; border-radius: 5px; background: #fff; color: #343b49; font: inherit; font-size: 13px; }
    .ar-action-form textarea { min-height: 76px; resize: vertical; }
    .ar-action-form input[readonly] { background: #f5f6f7; }
    .ar-action-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px 16px; }
    .ar-action-form-grid .ar-form-span { grid-column: 1 / -1; }
    .ar-action-info, .ar-action-warning { display: flex; align-items: flex-start; gap: 8px; margin: 13px 0; padding: 10px 11px; border-radius: 5px; background: #edf5fc; color: #355e80; font-size: 11px; line-height: 1.45; }
    .ar-action-info .material-symbols-rounded, .ar-action-warning .material-symbols-rounded { flex: 0 0 auto; font-size: 16px; }
    .ar-action-warning { background: #fff5e7; color: #9a5a08; }
    .ar-action-warning.is-danger { background: #fbeaea; color: #992c2c; }
    .ar-action-warning strong { margin-left: 3px; }
    .ar-action-preview-note { margin: 13px 0 0; color: #727781; font-size: 11px; }
    .ar-current-status, .ar-confirm-copy { margin: 0 0 12px; font-size: 12px; }
    .ar-current-status .ar-status { margin-left: 5px; }
    .ar-choice-list { display: grid; gap: 7px; margin: 10px 0 14px; }
    .ar-choice { display: flex !important; align-items: flex-start; gap: 9px; padding: 10px; border: 1px solid #e2e5e9; border-radius: 5px; cursor: pointer; }
    .ar-choice[hidden] { display: none !important; }
    .ar-choice:has(input:checked) { border-color: #687383; background: #f7f8fa; }
    .ar-choice input[type="radio"] { flex: 0 0 auto; margin: 2px 0 0; accent-color: #343b49; }
    .ar-choice strong, .ar-choice small { display: block; }
    .ar-choice strong { color: #343b49; font-size: 12px; }
    .ar-choice small { margin-top: 3px; color: #858b95; font-size: 10px; font-weight: 400; }
    .ar-action-label { margin-top: 12px !important; }
    .ar-action-fieldset { min-width: 0; margin: 0; padding: 0; border: 0; }
    .ar-action-fieldset .ar-choice-list { margin-top: 8px; }
    .ar-dialog-footer { margin-top: 13px; }
    .ar-icon-picker { min-width: 0; margin: 0 0 12px; padding: 0; border: 0; }
    .ar-icon-picker legend { margin-bottom: 7px; color: #626873; font-size: 12px; }
    .ar-icon-picker-options { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 7px; }
    .ar-icon-picker-options label { position: relative; display: flex; align-items: center; justify-content: center; height: 40px; margin: 0; border: 1px solid #dfe2e6; border-radius: 5px; color: #545c68; cursor: pointer; }
    .ar-icon-picker-options label:has(input:checked) { border-color: #343b49; background: #f1f2f4; color: #343b49; box-shadow: inset 0 0 0 1px #343b49; }
    .ar-icon-picker-options input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .ar-icon-picker-options .material-symbols-rounded { font-size: 20px; }
    .ar-detail-actions { flex-wrap: wrap; justify-content: flex-end; }
    .ar-detail-actions .ar-button.is-pending, .ar-detail-actions .ar-button.is-warning, .ar-detail-actions .ar-button.is-danger { color: #9a5a08; border-color: #e9c98f; }
    .ar-detail-actions .ar-button.is-danger { color: #b52f2f; border-color: #e9bcbc; }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    @media (max-width: 600px) {
        .ar-dialog { --ar-dialog-top: 12px; }
        .ar-action-form { padding: 13px 14px 15px; }
        .ar-action-form-grid { grid-template-columns: 1fr; gap: 10px; }
        .ar-action-form-grid .ar-form-span { grid-column: auto; }
        .ar-icon-picker-options { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .ar-tabs { gap: 18px; }
        .ar-list-footer { flex-wrap: wrap; }
        .ar-page-heading { gap: 6px; }
        .ar-page-heading p { max-width: 36ch; }
        .ar-preview-label { display: none; }
        .ar-heading-actions { margin-left: auto; }
        .ar-card-heading { align-items: flex-start; }
        .ar-card-heading h2 { padding-top: 7px; }
        .ar-table-wrap th, .ar-table-wrap td { padding: 9px 8px; }
        .ar-pending-card { padding: 11px 10px; }
        .ar-pending-item { align-items: flex-start; flex-wrap: wrap; }
        .ar-pending-item > p { flex-basis: 100%; padding-left: 37px; }
        .ar-pending-asset { flex-basis: calc(100% - 45px); }
        .ar-preview-actions { margin-left: 37px; }
        .ar-preview-actions .ar-button { padding: 6px 7px; font-size: 10px; }
        .ar-mine-list table { table-layout: fixed; }
        .ar-mine-list th:first-child { width: 65%; }
        .ar-mine-list th:nth-child(2), .ar-mine-list th:nth-child(3), .ar-mine-list th:nth-child(5),
        .ar-mine-list td:nth-child(2), .ar-mine-list td:nth-child(3), .ar-mine-list td:nth-child(5) { display: none; }
        .ar-mine-list th, .ar-mine-list td { padding: 10px 8px; }
        .ar-mine-list .ar-asset-cell { min-width: 0; }
        .ar-registry-toolbar { flex-wrap: wrap; }
        .ar-search { width: 100%; }
        .ar-registry-toolbar > label:not(.ar-search), .ar-registry-toolbar > select, .ar-registry-toolbar > .ts-wrapper { flex: 1 1 calc(50% - 7px); max-width: none; }
        .ar-registry-toolbar .ar-page-size { display: none; }
        .ar-registry-table { display: none; }
        .ar-registry-cards { display: block; }
        .ar-settings-card { flex: 1 1 50%; }
        .ar-registry-card { display: flex; align-items: center; gap: 9px; padding: 10px; border: 1px solid #e4e6ea; border-bottom: 0; background: #fff; color: inherit; font-size: 11px; text-decoration: none; }
        .ar-registry-card:first-child { border-radius: 6px 6px 0 0; }
        .ar-registry-card:nth-last-child(2) { border-bottom: 1px solid #e4e6ea; border-radius: 0 0 6px 6px; }
        .ar-registry-card-content { flex: 1; min-width: 0; }
        .ar-registry-card-content > .ar-status { margin-top: 5px; }
        .ar-registry-card-content > small .ar-avatar { margin-top: 4px; }
        .ar-card-chevron { color: #7b8189; font-size: 18px; }
        .ar-mobile-count { display: none; }
        .ar-settings-card .ar-table-wrap { overflow-x: auto; }
        .ar-settings-card .ar-table-wrap th, .ar-settings-card .ar-table-wrap td { padding: 8px 6px; }
        .ar-settings-card:first-of-type th:nth-child(3), .ar-settings-card:first-of-type td:nth-child(3) { display: none; }
        .ar-settings-card .ar-actions { width: 24px; }
        .ar-form-section { padding: 14px 12px; }
        .ar-form-grid { grid-template-columns: 1fr; gap: 10px; }
        .ar-form-span { grid-column: auto; }
        .ar-form-footer { padding: 12px; }
        .ar-detail-header { flex-wrap: wrap; }
        .ar-detail-title { flex: 1 1 calc(100% - 90px); }
        .ar-detail-title h1 { font-size: 16px; }
        .ar-detail-header > .ar-status { margin-left: 40px; }
        .ar-detail-actions { flex: 1 0 100%; justify-content: flex-start; margin-left: 40px; }
        .ar-detail-grid { grid-template-columns: 1fr; grid-template-rows: minmax(0, 1fr) auto; }
        .ar-detail-side { max-height: 36vh; }
        .ar-detail-fields { gap: 10px 12px; }
    }
</style>

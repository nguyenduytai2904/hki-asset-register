<div class="ar-action-dialogs" x-data="window.HK.assetRegister.actionDialogs()">
<dialog class="ar-dialog ar-action-dialog" data-ar-action-dialog="handover" aria-labelledby="ar-handover-title">
    <header class="ar-dialog-header">
        <h2 id="ar-handover-title"><span data-ar-context="action-title">{{ __('asset-register::asset-register.actions.handover_title') }}</span> · <span data-ar-context="code">—</span></h2>
        <button class="ar-dialog-close" type="button" data-ar-close-action-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
    </header>
    <form class="ar-action-form" data-ar-preview-form>
        <p class="ar-current-keeper" hidden data-transfer-title="{{ __('asset-register::asset-register.actions.transfer_title') }}">{{ __('asset-register::asset-register.actions.current_keeper') }} <strong data-ar-context="current-keeper">—</strong></p>
        <div class="ar-action-form-grid">
                <label class="ar-form-span">{{ __('asset-register::asset-register.actions.recipient') }} <span class="ar-required">*</span>
                    <input type="text" placeholder="{{ __('asset-register::asset-register.actions.recipient_placeholder') }}" required>
                <small class="ar-form-hint">{{ __('asset-register::asset-register.actions.active_member_hint') }}</small>
            </label>
            <label>{{ __('asset-register::asset-register.actions.condition_on_handover') }} <span class="ar-required">*</span>
                <select required>
                    <option value="new">{{ __('asset-register::asset-register.condition.new') }}</option>
                    <option value="good">{{ __('asset-register::asset-register.condition.good') }}</option>
                    <option value="fair">{{ __('asset-register::asset-register.condition.fair') }}</option>
                    <option value="poor">{{ __('asset-register::asset-register.condition.poor') }}</option>
                </select>
            </label>
            <label>{{ __('asset-register::asset-register.asset_form.location') }}
                <select>
                    <option>{{ __('asset-register::asset-register.actions.location_office') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.location_storage') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.location_server_room') }}</option>
                </select>
            </label>
            <label class="ar-form-span">{{ __('asset-register::asset-register.actions.handover_note') }}
                <textarea rows="3" maxlength="2000" placeholder="{{ __('asset-register::asset-register.actions.optional') }}"></textarea>
                <small class="ar-form-hint">{{ __('asset-register::asset-register.actions.no_personal_data') }}</small>
            </label>
        </div>
        <div class="ar-action-info"><span class="material-symbols-rounded" aria-hidden="true">mail</span>{{ __('asset-register::asset-register.actions.handover_info') }}</div>
        <p class="ar-action-preview-note" hidden>{{ __('asset-register::asset-register.actions.preview_only') }}</p>
        <footer class="ar-dialog-footer">
            <button class="ar-button" type="button" data-ar-close-action-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
            <button class="ar-button ar-button-dark" type="submit"><span class="material-symbols-rounded" aria-hidden="true">assignment_ind</span>{{ __('asset-register::asset-register.actions.handover_submit') }}</button>
        </footer>
    </form>
</dialog>

<dialog class="ar-dialog ar-action-dialog" data-ar-action-dialog="return" aria-labelledby="ar-return-title">
    <header class="ar-dialog-header">
        <h2 id="ar-return-title">{{ __('asset-register::asset-register.actions.return_title') }} · <span data-ar-context="code">—</span></h2>
        <button class="ar-dialog-close" type="button" data-ar-close-action-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
    </header>
    <form class="ar-action-form" data-ar-preview-form>
        <div class="ar-action-warning"><span class="material-symbols-rounded" aria-hidden="true">warning</span><span data-ar-return-warning data-default-text="{{ __('asset-register::asset-register.actions.return_warning') }}" data-pending-text="{{ __('asset-register::asset-register.actions.pending_return_warning') }}">{{ __('asset-register::asset-register.actions.return_warning') }}</span></div>
        <div class="ar-action-form-grid">
            <label>{{ __('asset-register::asset-register.actions.returning_member') }}
                <input type="text" data-ar-context-value="recipient" readonly>
            </label>
            <label>{{ __('asset-register::asset-register.actions.condition_on_return') }} <span class="ar-required">*</span>
                <select required>
                    <option value="new">{{ __('asset-register::asset-register.condition.new') }}</option>
                    <option value="good">{{ __('asset-register::asset-register.condition.good') }}</option>
                    <option value="fair">{{ __('asset-register::asset-register.condition.fair') }}</option>
                    <option value="poor">{{ __('asset-register::asset-register.condition.poor') }}</option>
                </select>
            </label>
            <label>{{ __('asset-register::asset-register.actions.store_at') }}
                <select>
                    <option>{{ __('asset-register::asset-register.actions.location_storage') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.location_office') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.location_server_room') }}</option>
                </select>
            </label>
            <label class="ar-form-span">{{ __('asset-register::asset-register.actions.return_note') }}
                <textarea rows="3" maxlength="2000" placeholder="{{ __('asset-register::asset-register.actions.optional') }}"></textarea>
                <small class="ar-form-hint">{{ __('asset-register::asset-register.actions.no_personal_data') }}</small>
            </label>
        </div>
        <p class="ar-action-preview-note" hidden>{{ __('asset-register::asset-register.actions.preview_only') }}</p>
        <footer class="ar-dialog-footer">
            <button class="ar-button" type="button" data-ar-close-action-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
            <button class="ar-button ar-button-dark" type="submit"><span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>{{ __('asset-register::asset-register.actions.return_submit') }}</button>
        </footer>
    </form>
</dialog>

<dialog class="ar-dialog ar-action-dialog" data-ar-action-dialog="status" aria-labelledby="ar-status-title">
    <header class="ar-dialog-header">
        <h2 id="ar-status-title">{{ __('asset-register::asset-register.actions.status_title') }} · <span data-ar-context="code">—</span></h2>
        <button class="ar-dialog-close" type="button" data-ar-close-action-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
    </header>
    <form class="ar-action-form" data-ar-preview-form>
        <p class="ar-current-status">{{ __('asset-register::asset-register.actions.current_status') }} <span class="ar-status" data-ar-context-status>—</span> · {{ __('asset-register::asset-register.actions.allowed_status_hint') }}</p>
        <div class="ar-choice-list">
            <label class="ar-choice" data-ar-status-choice="available"><input type="radio" name="status" value="available"><span><strong>{{ __('asset-register::asset-register.asset_status.available') }}</strong><small>{{ __('asset-register::asset-register.actions.status_available_hint') }}</small></span></label>
            <label class="ar-choice" data-ar-status-choice="in_repair"><input type="radio" name="status" value="in_repair"><span><strong>{{ __('asset-register::asset-register.asset_status.in_repair') }}</strong><small>{{ __('asset-register::asset-register.actions.status_repair_hint') }}</small></span></label>
            <label class="ar-choice" data-ar-status-choice="broken"><input type="radio" name="status" value="broken"><span><strong>{{ __('asset-register::asset-register.asset_status.broken') }}</strong><small>{{ __('asset-register::asset-register.actions.status_broken_hint') }}</small></span></label>
            <label class="ar-choice" data-ar-status-choice="lost"><input type="radio" name="status" value="lost"><span><strong>{{ __('asset-register::asset-register.asset_status.lost') }}</strong><small>{{ __('asset-register::asset-register.actions.status_lost_hint') }}</small></span></label>
        </div>
        <label class="ar-action-label">{{ __('asset-register::asset-register.actions.reason') }} <span class="ar-required">*</span>
            <textarea rows="3" minlength="5" maxlength="1000" required></textarea>
            <small class="ar-form-hint">{{ __('asset-register::asset-register.actions.reason_hint') }} {{ __('asset-register::asset-register.actions.no_personal_data') }}</small>
        </label>
        <p class="ar-action-preview-note" hidden>{{ __('asset-register::asset-register.actions.preview_only') }}</p>
        <footer class="ar-dialog-footer">
            <button class="ar-button" type="button" data-ar-close-action-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
            <button class="ar-button ar-button-dark" type="submit">{{ __('asset-register::asset-register.actions.save') }}</button>
        </footer>
    </form>
</dialog>

<dialog class="ar-dialog ar-action-dialog" data-ar-action-dialog="dispose" aria-labelledby="ar-dispose-title">
    <header class="ar-dialog-header">
        <h2 id="ar-dispose-title">{{ __('asset-register::asset-register.actions.dispose_title') }} · <span data-ar-context="code">—</span></h2>
        <button class="ar-dialog-close" type="button" data-ar-close-action-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
    </header>
    <form class="ar-action-form" data-ar-preview-form>
        <div class="ar-action-warning is-danger"><span class="material-symbols-rounded" aria-hidden="true">warning</span>{{ __('asset-register::asset-register.actions.dispose_warning') }} <strong data-ar-context="code">—</strong></div>
        <div class="ar-action-form-grid">
            <label>{{ __('asset-register::asset-register.actions.disposed_at') }} <span class="ar-required">*</span><input type="date" data-hk-datepicker="date" required></label>
            <label>{{ __('asset-register::asset-register.actions.disposal_method') }} <span class="ar-required">*</span>
                <select required>
                    <option>{{ __('asset-register::asset-register.actions.method_sale') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.method_destroy') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.method_gift') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.method_return') }}</option>
                    <option>{{ __('asset-register::asset-register.actions.method_other') }}</option>
                </select>
            </label>
            <label class="ar-form-span">{{ __('asset-register::asset-register.actions.disposal_basis') }} <span class="ar-required">*</span>
                <textarea rows="3" minlength="1" maxlength="1000" required></textarea>
                <small class="ar-form-hint">{{ __('asset-register::asset-register.actions.no_personal_data') }}</small>
            </label>
        </div>
        <p class="ar-action-preview-note" hidden>{{ __('asset-register::asset-register.actions.preview_only') }}</p>
        <footer class="ar-dialog-footer">
            <button class="ar-button" type="button" data-ar-close-action-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
            <button class="ar-button ar-button-danger" type="submit"><span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>{{ __('asset-register::asset-register.actions.dispose_submit') }}</button>
        </footer>
    </form>
</dialog>

<dialog class="ar-dialog ar-action-dialog" data-ar-action-dialog="confirm" aria-labelledby="ar-confirm-title">
    <header class="ar-dialog-header">
        <h2 id="ar-confirm-title">{{ __('asset-register::asset-register.actions.confirm_title') }}</h2>
        <button class="ar-dialog-close" type="button" data-ar-close-action-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
    </header>
    <form class="ar-action-form" data-ar-preview-form>
        <p class="ar-confirm-copy">{{ __('asset-register::asset-register.actions.confirm_question') }} <strong data-ar-context="code">—</strong> · <span data-ar-context="name">—</span>?</p>
        <div class="ar-action-info"><span class="material-symbols-rounded" aria-hidden="true">info</span><span>{{ __('asset-register::asset-register.actions.confirm_info') }} <strong>{{ __('asset-register::asset-register.actions.condition_on_handover') }}: <span data-ar-context="condition">—</span></strong> · {{ __('asset-register::asset-register.actions.assigned_by') }} <strong data-ar-context="assigned-by">—</strong> <span data-ar-context="assigned-at">—</span>.</span></div>
        <p class="ar-action-preview-note" hidden>{{ __('asset-register::asset-register.actions.preview_only') }}</p>
        <footer class="ar-dialog-footer">
            <button class="ar-button" type="button" data-ar-close-action-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
            <button class="ar-button ar-button-dark" type="submit"><span class="material-symbols-rounded" aria-hidden="true">task_alt</span>{{ __('asset-register::asset-register.actions.confirm_submit') }}</button>
        </footer>
    </form>
</dialog>

<dialog class="ar-dialog ar-action-dialog" data-ar-action-dialog="issue" aria-labelledby="ar-issue-title">
    <header class="ar-dialog-header">
        <h2 id="ar-issue-title">{{ __('asset-register::asset-register.actions.issue_title') }} · <span data-ar-context="code">—</span></h2>
        <button class="ar-dialog-close" type="button" data-ar-close-action-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
    </header>
    <form class="ar-action-form" data-ar-preview-form>
        <fieldset class="ar-action-fieldset">
            <legend>{{ __('asset-register::asset-register.actions.issue_type') }} <span class="ar-required">*</span></legend>
            <div class="ar-choice-list">
                <label class="ar-choice"><input type="radio" name="issue" value="damaged" checked><span><strong>{{ __('asset-register::asset-register.actions.issue_damaged') }}</strong><small>{{ __('asset-register::asset-register.actions.issue_damaged_hint') }}</small></span></label>
                <label class="ar-choice"><input type="radio" name="issue" value="lost"><span><strong>{{ __('asset-register::asset-register.asset_status.lost') }}</strong><small>{{ __('asset-register::asset-register.actions.issue_lost_hint') }}</small></span></label>
                <label class="ar-choice"><input type="radio" name="issue" value="incorrect"><span><strong>{{ __('asset-register::asset-register.actions.issue_incorrect') }}</strong><small>{{ __('asset-register::asset-register.actions.issue_incorrect_hint') }}</small></span></label>
            </div>
        </fieldset>
        <label class="ar-action-label">{{ __('asset-register::asset-register.actions.issue_description') }} <span class="ar-required">*</span>
            <textarea rows="4" minlength="10" maxlength="1000" required></textarea>
            <small class="ar-form-hint">{{ __('asset-register::asset-register.actions.issue_description_hint') }} {{ __('asset-register::asset-register.actions.no_personal_data') }}</small>
        </label>
        <div class="ar-action-info"><span class="material-symbols-rounded" aria-hidden="true">info</span>{{ __('asset-register::asset-register.actions.issue_info') }}</div>
        <p class="ar-action-preview-note" hidden>{{ __('asset-register::asset-register.actions.preview_only') }}</p>
        <footer class="ar-dialog-footer">
            <button class="ar-button" type="button" data-ar-close-action-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
            <button class="ar-button ar-button-dark" type="submit"><span class="material-symbols-rounded" aria-hidden="true">send</span>{{ __('asset-register::asset-register.actions.issue_submit') }}</button>
        </footer>
    </form>
</dialog>
</div>

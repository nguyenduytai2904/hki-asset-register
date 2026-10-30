function requestErrorMessage(error, fallback) {
    return error.response?.data?.message || fallback;
}

function withMultiSelectFilter(url, name, values) {
    const next = new URL(url.toString());
    const baseName = name.replace(/\[\]$/, '');

    for (const key of new Set(next.searchParams.keys())) {
        if (key === baseName || key.startsWith(`${baseName}[`)) {
            next.searchParams.delete(key);
        }
    }

    values.forEach((value) => next.searchParams.append(name, value));
    next.searchParams.delete('page');

    return next;
}

async function submitActionDialogForm(form) {
    const feedback = form.querySelector('[data-ar-action-feedback]');
    const submitButton = form.querySelector('button[type="submit"]');
    if (!feedback || !(submitButton instanceof HTMLButtonElement)) {
        throw new Error('The asset action form is missing its feedback or submit control.');
    }

    feedback.hidden = true;
    feedback.replaceChildren();
    form.setAttribute('aria-busy', 'true');
    submitButton.disabled = true;

    const requestJson = async () => {
        if (window.axios && typeof window.axios.post === 'function') {
            return window.axios.post(form.action, new FormData(form), {
                headers: { Accept: 'application/json' },
            });
        }

        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const payload = await response.clone().json().catch(() => null);
            const error = new Error('The request failed.');
            error.response = {
                status: response.status,
                data: payload || {},
            };
            throw error;
        }

        return { request: { responseURL: response.url } };
    };

    try {
        const response = await requestJson();
        const destination = response.request?.responseURL;
        if (!destination || destination === window.location.href) {
            window.location.reload();
            return;
        }

        window.location.assign(destination);
    } catch (error) {
        const errors = error.response?.data?.errors || error.response?.data?.fields;
        const messages = errors
            ? Object.values(errors).flat().filter((message) => typeof message === 'string')
            : [requestErrorMessage(error, feedback.dataset.errorMessage)];

        if (!error.response) console.error(error);
        const list = document.createElement('ul');
        messages.forEach((message) => {
            const item = document.createElement('li');
            item.textContent = message;
            list.append(item);
        });
        feedback.replaceChildren(list);
        feedback.hidden = false;
    } finally {
        form.removeAttribute('aria-busy');
        submitButton.disabled = false;
    }
}

function destroyAssetFormDatePickers(content) {
    content.querySelectorAll('[data-hk-datepicker-input]').forEach((input) => input._flatpickr?.destroy());
}

function enhanceAssetDatePicker(input) {
    const picker = input?._flatpickr;
    const calendar = picker?.calendarContainer;
    if (!picker || !calendar || calendar.dataset.arDateEnhanced === 'true') return;

    const currentMonth = calendar.querySelector('.flatpickr-current-month');
    const monthSelect = currentMonth?.querySelector('.flatpickr-monthDropdown-months');
    const yearInput = currentMonth?.querySelector('input.cur-year');
    const yearInputWrapper = yearInput?.closest('.numInputWrapper');
    if (!currentMonth || !monthSelect || !yearInput || !yearInputWrapper) return;

    const isVietnamese = document.documentElement.lang.toLowerCase().startsWith('vi');
    monthSelect.setAttribute('aria-label', isVietnamese ? 'Chọn tháng' : 'Select month');
    yearInput.setAttribute('aria-label', isVietnamese ? 'Chọn năm' : 'Select year');
    yearInput.title = isVietnamese ? 'Chọn năm' : 'Select year';
    yearInput.tabIndex = 0;
    yearInputWrapper.style.display = 'inline-block';

    calendar.dataset.arDateEnhanced = 'true';
    calendar.classList.add('ar-asset-datepicker');
}

function enhanceAssetDatePickers(root) {
    root.querySelectorAll('input[data-hk-datepicker]').forEach(enhanceAssetDatePicker);
}

function positionActionDialogDatePicker(picker, positionElement) {
    const anchor = positionElement || picker.altInput || picker.input;
    if (!(anchor instanceof HTMLElement)) return;

    const bounds = anchor.getBoundingClientRect();
    const calendar = picker.calendarContainer;
    const calendarHeight = calendar.offsetHeight;
    const calendarWidth = calendar.offsetWidth;
    const showAbove = window.innerHeight - bounds.bottom < calendarHeight && bounds.top > calendarHeight;
    const maxLeft = Math.max(8, window.innerWidth - calendarWidth - 8);
    const top = showAbove ? bounds.top - calendarHeight - 2 : bounds.bottom + 2;

    calendar.style.position = 'fixed';
    calendar.style.top = `${Math.max(8, Math.min(top, window.innerHeight - calendarHeight - 8))}px`;
    calendar.style.left = `${Math.min(Math.max(8, bounds.left), maxLeft)}px`;
    calendar.style.right = 'auto';
    calendar.classList.toggle('arrowTop', !showAbove);
    calendar.classList.toggle('arrowBottom', showAbove);
}

function initializeActionDialogDatePickers(dialog) {
    const inputs = dialog.querySelectorAll('input[data-ar-datepicker]');
    if (!inputs.length) return;

    const createDatePicker = window.HK?.datepicker?.create;
    if (typeof createDatePicker !== 'function') {
        dialog.classList.add('ar-dialog-datepicker-host');
        return;
    }

    dialog.classList.add('ar-dialog-datepicker-host');
    inputs.forEach((input) => {
        if (input._flatpickr) {
            enhanceAssetDatePicker(input);
            return;
        }

        try {
            createDatePicker(input, {
                mode: input.dataset.arDatepicker,
                flatpickr: {
                    appendTo: dialog,
                    position: positionActionDialogDatePicker,
                },
            });
            enhanceAssetDatePicker(input);
        } catch (error) {
            console.warn('Asset register datepicker could not be initialized for dialog.', error);
        }
    });
}

function clearAssetFormErrors(form) {
    form.querySelectorAll('[data-ar-validation-error]').forEach((error) => error.remove());
    form.querySelectorAll('[aria-invalid="true"]').forEach((input) => input.removeAttribute('aria-invalid'));
}

function addMonthsWithoutOverflow(dateValue, months) {
    const date = new Date(`${dateValue}T00:00:00.000Z`);
    if (Number.isNaN(date.getTime())) return '';

    const targetMonth = date.getUTCMonth() + months;
    const targetYear = date.getUTCFullYear() + Math.floor(targetMonth / 12);
    const monthIndex = targetMonth % 12;
    const lastDay = new Date(Date.UTC(targetYear, monthIndex + 1, 0)).getUTCDate();
    date.setUTCFullYear(targetYear, monthIndex, Math.min(date.getUTCDate(), lastDay));

    return date.toISOString().slice(0, 10);
}

function syncWarrantyFields(form, calculateEndDate = false) {
    if (!(form instanceof HTMLFormElement)) return;

    const durationSelect = form.querySelector('[data-ar-warranty-duration]');
    const customMonths = form.querySelector('[data-ar-warranty-custom-months]');
    const customField = form.querySelector('[data-ar-warranty-custom-field]');
    const purchaseDate = form.querySelector('[data-ar-warranty-purchase-date]');
    const purchaseRequired = form.querySelector('[data-ar-warranty-purchase-required]');
    const endDate = form.querySelector('[data-ar-warranty-end-date]');
    const durationValue = form.elements.namedItem('warranty_duration_months');
    if (!(durationSelect instanceof HTMLSelectElement) || !(customMonths instanceof HTMLInputElement)
        || !(purchaseDate instanceof HTMLInputElement) || !(endDate instanceof HTMLInputElement)) return;

    const isCustom = durationSelect.value === 'custom';
    const hasDuration = durationSelect.value !== '';
    if (customField instanceof HTMLElement) customField.hidden = !isCustom;
    customMonths.required = isCustom;
    purchaseDate.required = hasDuration;
    if (purchaseRequired instanceof HTMLElement) purchaseRequired.hidden = !hasDuration;

    const rawMonths = isCustom ? customMonths.value : durationSelect.value;
    const months = Number(rawMonths);
    if (durationValue instanceof HTMLInputElement) {
        durationValue.value = Number.isSafeInteger(months) && months >= 1 && months <= 1200 ? String(months) : '';
    }

    if (!calculateEndDate || !hasDuration || !purchaseDate.value
        || !Number.isSafeInteger(months) || months < 1 || months > 1200) return;

    const calculatedDate = addMonthsWithoutOverflow(purchaseDate.value, months);
    if (!calculatedDate) return;

    const setDatePickerValue = window.HK?.datepicker?.setValue;
    if (typeof setDatePickerValue === 'function') {
        setDatePickerValue(endDate, calculatedDate);
        return;
    }

    endDate.value = calculatedDate;
}

function updateWarrantyFromEvent(target) {
    if (!(target instanceof HTMLElement)) return;
    if (!target.matches('[data-ar-warranty-duration], [data-ar-warranty-purchase-date], [data-ar-warranty-custom-months]')) return;
    syncWarrantyFields(target.closest('form'), true);
}

async function openAssetForm(component, link) {
    const dialog = component.$el.querySelector('#ar-asset-form-dialog');
    const title = component.$el.querySelector('#ar-asset-form-dialog-title');
    const content = component.$el.querySelector('#ar-asset-form-dialog-content');
    const feedback = component.$el.querySelector('#ar-asset-form-dialog-feedback');
    if (!dialog || !title || !content || !feedback) return;

    let formLoaded = false;
    component.formController?.abort();
    const controller = new AbortController();
    component.formController = controller;
    feedback.hidden = true;

    try {
        const response = await window.axios.get(link.href, {
            headers: { Accept: 'text/html' },
            signal: controller.signal,
        });
        const parsed = new DOMParser().parseFromString(response.data, 'text/html');
        const sourceForm = parsed.querySelector('.ar-asset-form');
        const sourceTitle = parsed.querySelector('.ar-page-heading h1');
        if (!sourceForm || !sourceTitle) throw new Error('The asset form was missing from the response.');

        destroyAssetFormDatePickers(content);
        title.textContent = sourceTitle.textContent;
        content.replaceChildren(sourceForm.cloneNode(true));
        formLoaded = true;
        const cancelLink = content.querySelector('.ar-form-footer a');
        if (cancelLink) {
            const cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.className = cancelLink.className;
            cancelButton.dataset.arCloseAssetFormDialog = '';
            cancelButton.textContent = cancelLink.textContent;
            cancelLink.replaceWith(cancelButton);
        }

        syncWarrantyFields(content.querySelector('.ar-asset-form'));
        const createDatePicker = window.HK?.datepicker?.create;
        if (typeof createDatePicker !== 'function') {
            throw new Error('The platform date picker API is unavailable.');
        }
        content.querySelectorAll('input[data-hk-datepicker]').forEach((input) => {
            createDatePicker(input, { static: true });
        });
        enhanceAssetDatePickers(content);
        component.updateAssetCodePreview?.(content.querySelector('#ar-asset-type'));
        dialog.showModal();
    } catch (error) {
        if (error.code === 'ERR_CANCELED' || error.name === 'AbortError') return;
        console.error(error);
        if (formLoaded) {
            feedback.textContent = requestErrorMessage(error, component.config.i18n.saveError);
            feedback.hidden = false;
            dialog.showModal();
            return;
        }
        window.location.assign(link.href);
    }
}

async function submitAssetForm(component, event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('#ar-asset-form-dialog .ar-asset-form')) return;

    event.preventDefault();
    const feedback = component.$el.querySelector('#ar-asset-form-dialog-feedback');
    if (!feedback) return;

    clearAssetFormErrors(form);
    feedback.hidden = true;
    feedback.replaceChildren();
    form.setAttribute('aria-busy', 'true');
    component.formController?.abort();
    const controller = new AbortController();
    component.formController = controller;

    try {
        const response = await window.axios.post(form.action, new FormData(form), {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        const destination = response.request?.responseURL;
        if (!destination) {
            throw new Error('Asset save response did not redirect to the saved asset.');
        }

        if (destination === window.location.href) {
            window.location.reload();
            return;
        }

        window.location.assign(destination);
    } catch (error) {
        if (error.code === 'ERR_CANCELED' || error.name === 'AbortError') return;

        const errors = error.response?.data?.fields || error.response?.data?.errors;
        if (error.response?.status === 422 && errors) {
            let hasFieldErrors = false;
            Object.entries(errors).forEach(([field, messages]) => {
                let input = form.elements.namedItem(field);
                if (input instanceof RadioNodeList) input = input[0];
                if (!(input instanceof HTMLElement)) return;

                const message = Array.isArray(messages) ? messages[0] : messages;
                if (typeof message !== 'string') return;

                const errorMessage = document.createElement('small');
                errorMessage.className = 'ar-field-error';
                errorMessage.dataset.arValidationError = '';
                errorMessage.textContent = message;
                input.setAttribute('aria-invalid', 'true');
                (input.closest('label') || input.parentElement).append(errorMessage);
                hasFieldErrors = true;
            });

            if (!hasFieldErrors) {
                const list = document.createElement('ul');
                Object.values(errors).flat().forEach((message) => {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.append(item);
                });
                feedback.replaceChildren(list);
                feedback.hidden = false;
            }
            return;
        }

        console.error(error);
        feedback.textContent = requestErrorMessage(error, component.config.i18n.saveError);
        feedback.hidden = false;
    } finally {
        form.removeAttribute('aria-busy');
    }
}

function registryPage(config) {
    return {
        config,
        filterController: null,
        formController: null,
        init() {
            this.onChange = this.handleChange.bind(this);
            this.onClick = this.handleClick.bind(this);
            this.onSubmit = this.handleSubmit.bind(this);
            this.onInput = this.handleAssetFormInput.bind(this);
            this.$el.addEventListener('change', this.onChange, true);
            this.$el.addEventListener('click', this.onClick);
            this.$el.addEventListener('submit', this.onSubmit);
            this.$el.addEventListener('input', this.onInput);
        },
        destroy() {
            this.$el.removeEventListener('change', this.onChange, true);
            this.$el.removeEventListener('click', this.onClick);
            this.$el.removeEventListener('submit', this.onSubmit);
            this.$el.removeEventListener('input', this.onInput);
            this.filterController?.abort();
            this.formController?.abort();
            const content = this.$el.querySelector('#ar-asset-form-dialog-content');
            if (content) destroyAssetFormDatePickers(content);
        },
        async handleChange(event) {
            const target = event.target;
            updateWarrantyFromEvent(target);
            if (!(target instanceof HTMLSelectElement)) return;

            if (target.id === 'ar-asset-type') {
                this.updateAssetCodePreview(target);
            }

            if (!target.matches('[data-hk-table-filter][multiple]')) return;

            const container = target.closest('[data-hk-ajax-table]');
            const region = container?.querySelector('[data-hk-table-region]');
            if (!container || !region) return;

            event.stopImmediatePropagation();
            const selected = target.tomselect
                ? target.tomselect.getValue()
                : Array.from(target.selectedOptions, (option) => option.value);
            const values = Array.isArray(selected) ? selected : (selected ? [selected] : []);
            const url = withMultiSelectFilter(new URL(window.location.href), target.name, values);

            this.filterController?.abort();
            const controller = new AbortController();
            this.filterController = controller;
            region.setAttribute('aria-busy', 'true');

            try {
                const response = await window.axios.get(url.toString(), {
                    headers: { Accept: 'text/html' },
                    signal: controller.signal,
                });
                const parsed = new DOMParser().parseFromString(response.data, 'text/html');
                const updatedRegion = parsed.querySelector('[data-hk-table-region]');
                if (!updatedRegion) throw new Error('The asset filter response did not contain a table region.');

                region.replaceWith(updatedRegion);
                window.history.replaceState({}, '', url.pathname + url.search);
                window.dispatchEvent(new CustomEvent('hk:ajax-table-swapped', {
                    detail: { url: url.toString(), container },
                }));
            } catch (error) {
                if (error.code !== 'ERR_CANCELED' && error.name !== 'AbortError') {
                    console.error(error);
                    window.Toast.error(requestErrorMessage(error, this.config.i18n.filterError));
                }
            } finally {
                if (this.filterController === controller) {
                    container.querySelector('[data-hk-table-region]')?.setAttribute('aria-busy', 'false');
                }
            }
        },
        async handleClick(event) {
            const target = event.target;
            if (!(target instanceof Element)) return;

            const addAssetLink = target.closest('a.ar-add-asset');
            if (addAssetLink) {
                event.preventDefault();
                await openAssetForm(this, addAssetLink);
                return;
            }

            if (target.closest('[data-ar-close-asset-form-dialog]')) {
                this.$el.querySelector('#ar-asset-form-dialog')?.close();
            }
        },
        handleAssetFormInput(event) {
            const input = event.target;
            updateWarrantyFromEvent(input);
            if (!(input instanceof HTMLElement) || input.getAttribute('aria-invalid') !== 'true') return;
            input.removeAttribute('aria-invalid');
            input.closest('label')?.querySelectorAll('[data-ar-validation-error]').forEach((error) => error.remove());
        },
        async handleSubmit(event) {
            await submitAssetForm(this, event);
        },
        updateAssetCodePreview(typeSelect) {
            const preview = typeSelect?.closest('.ar-asset-form')?.querySelector('#ar-next-code');
            if (!preview) return;
            preview.textContent = typeSelect.selectedOptions[0]?.dataset.nextCode || preview.dataset.emptyValue || preview.textContent;
        },
    };
}

function assetFormDialog(config) {
    return {
        config,
        formController: null,
        init() {
            this.onClick = async (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;

                const editLink = target.closest('a.ar-edit-asset');
                if (editLink) {
                    event.preventDefault();
                    await openAssetForm(this, editLink);
                    return;
                }

                if (target.closest('[data-ar-close-asset-form-dialog]')) {
                    this.$el.querySelector('#ar-asset-form-dialog')?.close();
                }
            };
            this.onSubmit = (event) => submitAssetForm(this, event);
            this.onChange = (event) => updateWarrantyFromEvent(event.target);
            this.onInput = (event) => {
                const input = event.target;
                updateWarrantyFromEvent(input);
                if (!(input instanceof HTMLElement) || input.getAttribute('aria-invalid') !== 'true') return;
                input.removeAttribute('aria-invalid');
                input.closest('label')?.querySelectorAll('[data-ar-validation-error]').forEach((error) => error.remove());
            };
            this.$el.addEventListener('click', this.onClick);
            this.$el.addEventListener('change', this.onChange);
            this.$el.addEventListener('submit', this.onSubmit);
            this.$el.addEventListener('input', this.onInput);
        },
        destroy() {
            this.$el.removeEventListener('click', this.onClick);
            this.$el.removeEventListener('change', this.onChange);
            this.$el.removeEventListener('submit', this.onSubmit);
            this.$el.removeEventListener('input', this.onInput);
            this.formController?.abort();
            const content = this.$el.querySelector('#ar-asset-form-dialog-content');
            if (content) destroyAssetFormDatePickers(content);
        },
    };
}

function assetFormPage() {
    return {
        init() {
            this.onChange = (event) => {
                const target = event.target;
                updateWarrantyFromEvent(target);
                if (target instanceof HTMLSelectElement && target.id === 'ar-asset-type') {
                    this.updateCodePreview(target);
                }
            };
            this.onInput = (event) => updateWarrantyFromEvent(event.target);
            this.onFocusIn = () => enhanceAssetDatePickers(this.$el);
            this.$el.addEventListener('change', this.onChange);
            this.$el.addEventListener('input', this.onInput);
            this.$el.addEventListener('focusin', this.onFocusIn);
            syncWarrantyFields(this.$el.querySelector('.ar-asset-form'));
            enhanceAssetDatePickers(this.$el);
            this.updateCodePreview(this.$el.querySelector('#ar-asset-type'));
        },
        destroy() {
            this.$el.removeEventListener('change', this.onChange);
            this.$el.removeEventListener('input', this.onInput);
            this.$el.removeEventListener('focusin', this.onFocusIn);
        },
        updateCodePreview(typeSelect) {
            const preview = this.$el.querySelector('#ar-next-code');
            if (!preview) return;
            preview.textContent = typeSelect?.selectedOptions[0]?.dataset.nextCode || preview.dataset.emptyValue || preview.textContent;
        },
    };
}

function settingsPage(config) {
    return {
        config,
        init() {
            this.onInput = (event) => {
                const input = event.target;
                if (!(input instanceof HTMLInputElement) || input.name !== 'prefix') return;
                const preview = this.$el.querySelector('[data-ar-first-code]');
                if (preview) {
                    const prefix = input.value.trim().toUpperCase();
                    preview.textContent = prefix ? `${prefix}-0001` : preview.dataset.emptyValue;
                }
            };
            this.onClick = (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;
                const openButton = target.closest('[data-ar-open-dialog]');
                if (openButton) {
                    this.$el.querySelector(`#${CSS.escape(openButton.dataset.arOpenDialog)}`)?.showModal();
                    return;
                }

                target.closest('[data-ar-close-dialog]')?.closest('dialog')?.close();
            };
            this.onSubmit = (event) => {
                const submitter = event.submitter;
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || !(submitter instanceof HTMLElement) || !submitter.dataset.arConfirmation) return;
                if (form.dataset.arConfirmApproved) {
                    delete form.dataset.arConfirmApproved;
                    return;
                }

                event.preventDefault();
                if (form.dataset.arConfirmPending) return;
                form.dataset.arConfirmPending = 'true';
                const message = this.config.i18n.confirmations[submitter.dataset.arConfirmation];
                if (!message) {
                    delete form.dataset.arConfirmPending;
                    throw new Error(`Missing delete confirmation text for ${submitter.dataset.arConfirmation}.`);
                }

                const editDialog = submitter.closest('dialog');
                editDialog?.close();
                const reopenEditDialog = () => {
                    if (!editDialog?.isConnected || editDialog.open) return;
                    editDialog.showModal();
                    submitter.focus();
                };

                (async () => {
                    try {
                        const confirmed = await window.HK.showConfirmModal({
                            title: this.config.i18n.confirmTitle,
                            message,
                            confirmText: this.config.i18n.delete,
                            icon: 'warning',
                        });
                        if (!confirmed) {
                            reopenEditDialog();
                            return;
                        }
                        form.dataset.arConfirmApproved = 'true';
                        form.requestSubmit(submitter);
                    } catch (error) {
                        console.error(error);
                        window.Toast.error(error.response?.data?.message || message);
                        reopenEditDialog();
                    } finally {
                        delete form.dataset.arConfirmPending;
                    }
                })();
            };
            this.onDialogClick = (event) => {
                if (event.target === event.currentTarget) event.currentTarget.close();
            };
            this.$el.addEventListener('input', this.onInput);
            this.$el.addEventListener('click', this.onClick);
            this.$el.addEventListener('submit', this.onSubmit);
            this.$el.querySelectorAll('.ar-dialog').forEach((dialog) => dialog.addEventListener('click', this.onDialogClick));

            const failedDialog = this.$el.dataset.arFailedDialog;
            if (failedDialog) this.$el.querySelector(`#${CSS.escape(`ar-${failedDialog}-dialog`)}`)?.showModal();
        },
        destroy() {
            this.$el.removeEventListener('input', this.onInput);
            this.$el.removeEventListener('click', this.onClick);
            this.$el.removeEventListener('submit', this.onSubmit);
            this.$el.querySelectorAll('.ar-dialog').forEach((dialog) => dialog.removeEventListener('click', this.onDialogClick));
        },
    };
}

function actionDialogs() {
    return {
        init() {
            this.dialogs = [...this.$el.querySelectorAll('[data-ar-action-dialog]')];
            this.defaultText = new WeakMap();
            this.dialogs.forEach((dialog) => {
                dialog.querySelectorAll('[data-ar-context], [data-ar-context-status]').forEach((element) => {
                    this.defaultText.set(element, element.textContent);
                });
                dialog.addEventListener('click', this.onDialogClick);
                dialog.addEventListener('submit', this.onDialogSubmit);
            });

            this.onDocumentClick = this.handleDocumentClick.bind(this);
            document.addEventListener('click', this.onDocumentClick);

            const failedDialog = this.$el.dataset.arFailedDialog;
            if (failedDialog) {
                const trigger = this.$el.querySelector(`[data-ar-open-action-dialog="${CSS.escape(failedDialog)}"]`);
                const dialog = this.dialogs.find((item) => item.dataset.arActionDialog === failedDialog);
                if (trigger && dialog) this.openActionDialog(dialog, trigger, false);
            }
        },
        destroy() {
            document.removeEventListener('click', this.onDocumentClick);
            this.dialogs.forEach((dialog) => {
                dialog.removeEventListener('click', this.onDialogClick);
                dialog.removeEventListener('submit', this.onDialogSubmit);
            });
        },
        onDialogClick(event) {
            if (event.target === event.currentTarget) event.currentTarget.close();
            const target = event.target;
            if (target instanceof Element && target.closest('[data-ar-close-action-dialog]')) {
                target.closest('dialog')?.close();
            }
        },
        onDialogSubmit(event) {
            if (!(event.target instanceof HTMLFormElement)) return;
            const form = event.target;
            if (form.hasAttribute('data-ar-submit-form')) {
                event.preventDefault();
                void submitActionDialogForm(form);
                return;
            }
            if (form.hasAttribute('data-ar-preview-form')) {
                event.preventDefault();
                const note = form.querySelector('.ar-action-preview-note');
                if (note) note.hidden = false;
            }
        },
        handleDocumentClick(event) {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const trigger = target.closest('[data-ar-open-action-dialog]');
            if (!trigger) return;

            const dialog = this.dialogs.find((item) => item.dataset.arActionDialog === trigger.dataset.arOpenActionDialog);
            if (!dialog) return;

            this.openActionDialog(dialog, trigger);
        },
        openActionDialog(dialog, trigger, resetForm = true) {
            if (resetForm) {
                dialog.querySelectorAll('form').forEach((form) => form.reset());
                dialog.querySelectorAll('input[data-ar-datepicker]').forEach((input) => {
                    input._flatpickr?.setDate(input.value || null, false);
                });
            }
            dialog.querySelectorAll('[data-ar-context]').forEach((element) => {
                const key = element.dataset.arContext;
                const dataKey = key.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
                element.textContent = trigger.dataset[dataKey] || this.defaultText.get(element);
            });

            const currentKeeper = dialog.querySelector('.ar-current-keeper');
            if (currentKeeper) currentKeeper.hidden = trigger.dataset.actionTitle !== currentKeeper.dataset.transferTitle;
            dialog.querySelectorAll('[data-ar-context-value]').forEach((element) => {
                element.value = trigger.dataset[element.dataset.arContextValue] || '';
            });

            const status = dialog.querySelector('[data-ar-context-status]');
            if (status) {
                status.textContent = trigger.dataset.statusLabel || this.defaultText.get(status);
                status.className = `ar-status ${trigger.dataset.statusClass || ''}`.trim();
            }

            const allowedStatuses = trigger.dataset.statusOptions?.split(',') || [];
            const statusChoices = [...dialog.querySelectorAll('[data-ar-status-choice]')];
            statusChoices.forEach((choice) => {
                choice.hidden = !allowedStatuses.includes(choice.dataset.arStatusChoice);
            });
            const firstStatus = statusChoices.find((choice) => !choice.hidden)?.querySelector('input');
            const selectedStatus = statusChoices.find((choice) => choice.querySelector('input')?.checked);
            if (firstStatus && (resetForm || !selectedStatus || selectedStatus.hidden)) firstStatus.checked = true;

            const issueType = trigger.dataset.issueType;
            if (issueType) {
                const issueChoice = [...dialog.querySelectorAll('input[name="issue"]')].find((input) => input.value === issueType);
                if (issueChoice) issueChoice.checked = true;
            }

            const warning = dialog.querySelector('[data-ar-return-warning]');
            if (warning) {
                warning.textContent = trigger.dataset.pending === 'true'
                    ? warning.dataset.pendingText
                    : warning.dataset.defaultText;
            }

            const disposalDate = dialog.querySelector('input[name="disposed_at"]');
            if (disposalDate) {
                if (trigger.dataset.purchaseDate) disposalDate.min = trigger.dataset.purchaseDate;
                if (trigger.dataset.today) disposalDate.max = trigger.dataset.today;
                if (!disposalDate.value) {
                    disposalDate.value = '';
                }
            }

            dialog.querySelectorAll('.ar-action-preview-note').forEach((note) => { note.hidden = true; });
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', 'open');
            }
            initializeActionDialogDatePickers(dialog);

            if (disposalDate) {
                const picker = disposalDate._flatpickr;
                picker?.set('minDate', disposalDate.min || undefined);
                picker?.set('maxDate', disposalDate.max || undefined);

                const dateUnavailable = Boolean(
                    disposalDate.min && disposalDate.max && disposalDate.min > disposalDate.max,
                );
                const unavailableMessage = dialog.querySelector('[data-ar-disposal-date-unavailable]');
                const submitButton = disposalDate.form?.querySelector('button[type="submit"]');
                if (unavailableMessage instanceof HTMLElement) unavailableMessage.hidden = !dateUnavailable;
                if (submitButton instanceof HTMLButtonElement) submitButton.disabled = dateUnavailable;
                disposalDate.disabled = dateUnavailable;
            }
        },
    };
}

export { registryPage, assetFormDialog, assetFormPage, settingsPage, actionDialogs, withMultiSelectFilter };

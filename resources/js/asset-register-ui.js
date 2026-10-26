function requestErrorMessage(error, fallback) {
    return error.response?.data?.message || fallback;
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
            this.$el.addEventListener('change', this.onChange, true);
            this.$el.addEventListener('click', this.onClick);
            this.$el.addEventListener('submit', this.onSubmit);
        },
        destroy() {
            this.$el.removeEventListener('change', this.onChange, true);
            this.$el.removeEventListener('click', this.onClick);
            this.$el.removeEventListener('submit', this.onSubmit);
            this.filterController?.abort();
            this.formController?.abort();
        },
        async handleChange(event) {
            const target = event.target;
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
            const url = new URL(window.location.href);
            url.searchParams.delete(target.name);
            values.forEach((value) => url.searchParams.append(target.name, value));
            url.searchParams.delete('page');

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
                await this.openAssetForm(addAssetLink);
                return;
            }

            if (target.closest('[data-ar-close-asset-dialog]')) {
                this.$el.querySelector('#ar-asset-create-dialog')?.close();
            }
        },
        async openAssetForm(link) {
            const dialog = this.$el.querySelector('#ar-asset-create-dialog');
            const content = this.$el.querySelector('#ar-asset-dialog-content');
            const feedback = this.$el.querySelector('#ar-asset-dialog-feedback');
            if (!dialog || !content || !feedback) return;

            this.formController?.abort();
            const controller = new AbortController();
            this.formController = controller;
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

                this.$el.querySelector('#ar-asset-dialog-title').textContent = sourceTitle.textContent;
                content.replaceChildren(sourceForm.cloneNode(true));
                const cancelLink = content.querySelector('.ar-form-footer a');
                if (cancelLink) {
                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = cancelLink.className;
                    cancelButton.dataset.arCloseAssetDialog = '';
                    cancelButton.textContent = cancelLink.textContent;
                    cancelLink.replaceWith(cancelButton);
                }

                this.updateAssetCodePreview(content.querySelector('#ar-asset-type'));
                dialog.showModal();
            } catch (error) {
                if (error.code === 'ERR_CANCELED' || error.name === 'AbortError') return;
                console.error(error);
                window.location.assign(link.href);
            }
        },
        async handleSubmit(event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.matches('#ar-asset-create-dialog .ar-asset-form')) return;

            event.preventDefault();
            const dialog = this.$el.querySelector('#ar-asset-create-dialog');
            const feedback = this.$el.querySelector('#ar-asset-dialog-feedback');
            if (!dialog || !feedback) return;

            feedback.hidden = true;
            feedback.replaceChildren();
            form.setAttribute('aria-busy', 'true');
            this.formController?.abort();
            const controller = new AbortController();
            this.formController = controller;

            try {
                const response = await window.axios.post(form.action, new FormData(form), {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const destination = response.request?.responseURL;
                if (!destination || destination === window.location.href) {
                    throw new Error('Asset save response did not redirect to the saved asset.');
                }

                window.location.assign(destination);
            } catch (error) {
                if (error.code === 'ERR_CANCELED' || error.name === 'AbortError') return;

                const errors = error.response?.data?.fields || error.response?.data?.errors;
                if (error.response?.status === 422 && errors) {
                    const messages = Object.values(errors).flat();
                    if (messages.length > 0) {
                        const list = document.createElement('ul');
                        messages.forEach((message) => {
                            const item = document.createElement('li');
                            item.textContent = message;
                            list.append(item);
                        });
                        feedback.replaceChildren(list);
                        feedback.hidden = false;
                        return;
                    }
                }

                console.error(error);
                feedback.textContent = requestErrorMessage(error, this.config.i18n.saveError);
                feedback.hidden = false;
            } finally {
                form.removeAttribute('aria-busy');
            }
        },
        updateAssetCodePreview(typeSelect) {
            const preview = typeSelect?.closest('.ar-asset-form')?.querySelector('#ar-next-code');
            if (!preview) return;
            preview.textContent = typeSelect.selectedOptions[0]?.dataset.nextCode || preview.dataset.emptyValue || preview.textContent;
        },
    };
}

function assetFormPage() {
    return {
        init() {
            this.onChange = (event) => {
                if (event.target instanceof HTMLSelectElement && event.target.id === 'ar-asset-type') {
                    this.updateCodePreview(event.target);
                }
            };
            this.$el.addEventListener('change', this.onChange);
            this.updateCodePreview(this.$el.querySelector('#ar-asset-type'));
        },
        destroy() {
            this.$el.removeEventListener('change', this.onChange);
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
                window.HK.showConfirmModal({
                    title: this.config.i18n.confirmTitle,
                    message,
                    confirmText: this.config.i18n.delete,
                    icon: 'warning',
                }).then((confirmed) => {
                    if (!confirmed) return;
                    form.dataset.arConfirmApproved = 'true';
                    form.requestSubmit(submitter);
                }).catch((error) => {
                    console.error(error);
                    window.Toast.error(error.response?.data?.message || message);
                }).finally(() => {
                    delete form.dataset.arConfirmPending;
                });
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
            event.preventDefault();
            const note = event.target.querySelector('.ar-action-preview-note');
            if (note) note.hidden = false;
        },
        handleDocumentClick(event) {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const trigger = target.closest('[data-ar-open-action-dialog]');
            if (!trigger) return;

            const dialog = this.dialogs.find((item) => item.dataset.arActionDialog === trigger.dataset.arOpenActionDialog);
            if (!dialog) return;

            dialog.querySelectorAll('form').forEach((form) => form.reset());
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
            if (firstStatus) firstStatus.checked = true;

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

            const disposalDate = dialog.querySelector('input[type="date"]');
            if (disposalDate && trigger.dataset.purchaseDate) disposalDate.min = trigger.dataset.purchaseDate;
            if (disposalDate) {
                const today = new Date();
                disposalDate.max = [
                    today.getFullYear(),
                    String(today.getMonth() + 1).padStart(2, '0'),
                    String(today.getDate()).padStart(2, '0'),
                ].join('-');
            }

            dialog.querySelectorAll('.ar-action-preview-note').forEach((note) => { note.hidden = true; });
            dialog.showModal();
        },
    };
}

export { registryPage, assetFormPage, settingsPage, actionDialogs };

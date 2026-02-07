(function(window) {
    'use strict';

    const KeyboardManager = {
        actions: {},
        globalKeys: ['F2','F3','F4','F6','F7','F8','F9','Escape'],
        arrowKeys: ['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'],
        modalOpen: false,
        enabled: true,
        primaryInput: null,
        lastFocusedElement: null,

        init: function(options = {}) {
            this.primaryInput = options.primaryInput || document.getElementById('barcodeInput');
            this.setupEventListeners();
            this.setupModalListeners();
            this.focusPrimaryInput();
            return this;
        },

        setupEventListeners: function() {
            const self = this;

            document.addEventListener('keydown', function(e) {
                if (!self.enabled) return;

                const key = e.key;
                const isGlobalKey = self.globalKeys.includes(key);
                const isArrowKey = self.arrowKeys.includes(key);
                const isInInput = self.isInputFocused();
                const isInModal = self.isModalInputFocused();

                if (isGlobalKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    if (self.modalOpen && key === 'Escape') {
                        self.closeActiveModal();
                        return;
                    }

                    if (self.actions[key]) {
                        self.actions[key](e);
                    }
                    return;
                }

                if (isArrowKey && !isInModal) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (self.actions[key]) {
                        self.actions[key](e);
                    }
                    return;
                }

                if (key === 'Enter' && !isInModal) {
                    if (self.actions['Enter']) {
                        self.actions['Enter'](e);
                    }
                }

                if (key === 'Delete' && !isInInput) {
                    e.preventDefault();
                    if (self.actions['Delete']) {
                        self.actions['Delete'](e);
                    }
                }

                if (key === '+' && !isInInput) {
                    e.preventDefault();
                    if (self.actions['Plus']) {
                        self.actions['Plus'](e);
                    }
                }

                if (key === '-' && !isInInput && e.target.type !== 'number') {
                    e.preventDefault();
                    if (self.actions['Minus']) {
                        self.actions['Minus'](e);
                    }
                }

                if (key === ' ' && !isInModal) {
                    const el = document.activeElement;
                    const isPrimaryInput = el === self.primaryInput;
                    const isEmpty = isPrimaryInput && el.value.trim() === '';
                    if (isEmpty || !isInInput) {
                        e.preventDefault();
                        if (self.actions[' ']) {
                            self.actions[' '](e);
                        }
                    }
                }
            }, true);

            window.addEventListener('keydown', function(e) {
                if (self.globalKeys.includes(e.key) || self.arrowKeys.includes(e.key)) {
                    e.preventDefault();
                }
            }, true);
        },

        setupModalListeners: function() {
            const self = this;

            document.addEventListener('show.bs.modal', function(e) {
                self.modalOpen = true;
                self.lastFocusedElement = document.activeElement;

                setTimeout(function() {
                    const modal = e.target;
                    const autofocusEl = modal.querySelector('[autofocus]');
                    const firstInput = modal.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');

                    if (autofocusEl) {
                        autofocusEl.focus();
                    } else if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            });

            document.addEventListener('hidden.bs.modal', function(e) {
                const openModals = document.querySelectorAll('.modal.show');
                self.modalOpen = openModals.length > 0;

                if (!self.modalOpen) {
                    self.focusPrimaryInput();
                }
            });
        },

        register: function(key, callback) {
            this.actions[key] = callback;
            return this;
        },

        registerMultiple: function(mappings) {
            for (const key in mappings) {
                this.actions[key] = mappings[key];
            }
            return this;
        },

        unregister: function(key) {
            delete this.actions[key];
            return this;
        },

        isInputFocused: function() {
            const el = document.activeElement;
            if (!el) return false;
            const tag = el.tagName;
            return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
        },

        isModalInputFocused: function() {
            const el = document.activeElement;
            if (!el) return false;
            const modal = el.closest('.modal');
            if (!modal) return false;
            const tag = el.tagName;
            return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
        },

        focusPrimaryInput: function() {
            if (this.primaryInput && !this.modalOpen) {
                setTimeout(() => {
                    this.primaryInput.focus();
                    this.primaryInput.select();
                }, 50);
            }
        },

        closeActiveModal: function() {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const instance = bootstrap.Modal.getInstance(modal);
                if (instance) {
                    instance.hide();
                }
            }
        },

        enable: function() {
            this.enabled = true;
            return this;
        },

        disable: function() {
            this.enabled = false;
            return this;
        },

        isEnabled: function() {
            return this.enabled;
        },

        isModalOpen: function() {
            return this.modalOpen;
        },

        setPrimaryInput: function(element) {
            this.primaryInput = element;
            return this;
        },

        trigger: function(key) {
            if (this.actions[key]) {
                this.actions[key](new KeyboardEvent('keydown', { key: key }));
            }
            return this;
        }
    };

    window.KeyboardManager = KeyboardManager;

})(window);

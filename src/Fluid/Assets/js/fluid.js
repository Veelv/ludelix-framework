/**
 * Fluid Framework - Component Logic
 * Version 1.0.0
 */

(function () {
    'use strict';

    // --- MODAL COMPONENT ---
    const Modal = {
        init() {
            // Setup triggers
            document.querySelectorAll('[data-fl-toggle="modal"]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = trigger.getAttribute('data-fl-target');
                    this.open(targetId);
                });
            });

            // Setup close buttons
            document.querySelectorAll('[data-fl-dismiss="modal"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const modal = btn.closest('.fl-modal');
                    this.close(modal);
                });
            });

            // Setup click outside
            window.addEventListener('click', (e) => {
                if (e.target.classList.contains('fl-modal')) {
                    this.close(e.target);
                }
            });
        },

        open(targetId) {
            const modal = document.querySelector(targetId);
            if (modal) {
                modal.style.display = 'block';
                // Trigger reflow
                modal.offsetHeight;
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        },

        close(modal) {
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    if (!document.querySelector('.fl-modal.show, .fl-bottomsheet.show')) {
                        document.body.style.overflow = '';
                    }
                }, 300); // Match CSS transition
            }
        }
    };

    // --- BOTTOM SHEET COMPONENT ---
    const BottomSheet = {
        init() {
            // Setup triggers
            document.querySelectorAll('[data-fl-toggle="bottomsheet"]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = trigger.getAttribute('data-fl-target');
                    this.open(targetId);
                });
            });

            // Setup close buttons
            document.querySelectorAll('[data-fl-dismiss="bottomsheet"], [data-fl-dismiss="modal"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const sheet = btn.closest('.fl-bottomsheet');
                    if (sheet) this.close(sheet);

                    const modal = btn.closest('.fl-modal');
                    if (modal) Modal.close(modal);
                });
            });

            // Setup click outside
            window.addEventListener('click', (e) => {
                if (e.target.classList.contains('fl-bottomsheet')) {
                    this.close(e.target);
                }
            });
        },

        open(targetId) {
            const sheet = document.querySelector(targetId);
            if (sheet) {
                sheet.style.display = 'block';
                // Trigger reflow
                sheet.offsetHeight;
                sheet.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        },

        close(sheet) {
            if (sheet) {
                sheet.classList.remove('show');
                setTimeout(() => {
                    sheet.style.display = 'none';
                    if (!document.querySelector('.fl-modal.show, .fl-bottomsheet.show')) {
                        document.body.style.overflow = '';
                    }
                }, 300);
            }
        }
    };

    // --- TOOLTIP COMPONENT ---
    const Tooltip = {
        init() {
            document.querySelectorAll('[data-fl-toggle="tooltip"]').forEach(el => {
                const title = el.getAttribute('title');
                if (!title) return;

                // Create tooltip element
                el.setAttribute('data-original-title', title);
                el.removeAttribute('title');

                el.addEventListener('mouseenter', () => this.show(el));
                el.addEventListener('mouseleave', () => this.hide(el));
            });
        },

        show(el) {
            const title = el.getAttribute('data-original-title');
            let tooltip = document.getElementById('fl-tooltip-active');

            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.id = 'fl-tooltip-active';
                tooltip.className = 'fl-tooltip';
                tooltip.innerHTML = `<div class="fl-tooltip-inner">${title}</div>`;
                document.body.appendChild(tooltip);
            } else {
                tooltip.querySelector('.fl-tooltip-inner').textContent = title;
            }

            // Positioning (Simple Top positioning for now)
            const rect = el.getBoundingClientRect();
            tooltip.classList.add('show');

            const tooltipRect = tooltip.getBoundingClientRect();

            // Calculate center
            const top = rect.top - tooltipRect.height - 5;
            const left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

            tooltip.style.top = `${top + window.scrollY}px`;
            tooltip.style.left = `${left + window.scrollX}px`;
        },

        hide(el) {
            const tooltip = document.getElementById('fl-tooltip-active');
            if (tooltip) {
                tooltip.classList.remove('show');
                // Remove from DOM after transition
                setTimeout(() => {
                    if (tooltip && !tooltip.classList.contains('show')) {
                        tooltip.remove();
                    }
                }, 200);
            }
        }
    };

    // Initialize on DOM Ready
    document.addEventListener('DOMContentLoaded', () => {
        Modal.init();
        BottomSheet.init();
        Tooltip.init();
    });

})();

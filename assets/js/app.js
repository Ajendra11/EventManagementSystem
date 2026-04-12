/**
 * EventHub — client-side validation & UI enhancements
 */
document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;

    // ── Form validation ───────────────────────────────────────────
    document.querySelectorAll('form[data-validate="true"]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var errors = [];

            form.querySelectorAll('[data-required="true"]').forEach(function (field) {
                if (!field.value.trim()) {
                    var label = field.getAttribute('data-label') || field.name || 'Field';
                    errors.push(label + ' is required.');
                    field.style.borderColor = '#dc2626';
                } else {
                    field.style.borderColor = '';
                }
            });

            var pass = form.querySelector('input[name="password"], input[name="new_password"]');
            var confirm = form.querySelector('input[name="confirm_password"], input[name="confirm_new_password"]');

            if (pass && confirm && pass.value && confirm.value && pass.value !== confirm.value) {
                errors.push('Passwords do not match.');
                confirm.style.borderColor = '#dc2626';
            }

            var qtyField = form.querySelector('input[name="quantity"]');
            if (qtyField) {
                var qty = parseInt(qtyField.value, 10);
                var max = parseInt(qtyField.getAttribute('max') || '10', 10);

                if (isNaN(qty) || qty < 1) {
                    errors.push('Please select at least 1 seat.');
                } else if (qty > max) {
                    errors.push('Maximum ' + max + ' seat(s) allowed per booking.');
                }
            }

            var starPicker = form.querySelector('.star-picker');
            if (starPicker && !form.querySelector('.star-picker input[type=radio]:checked')) {
                errors.push('Please select a star rating.');
            }

            if (errors.length) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });

        form.querySelectorAll('[data-required="true"]').forEach(function (field) {
            field.addEventListener('input', function () {
                if (this.value.trim()) {
                    this.style.borderColor = '';
                }
            });
        });
    });

    // ── Character counters ────────────────────────────────────────
    document.querySelectorAll('textarea[maxlength]').forEach(function (ta) {
        var counter = null;
        var small = ta.nextElementSibling;

        if (small && small.tagName === 'SMALL') {
            var span = small.querySelector('span');
            if (span) {
                counter = span;
            }
        }

        if (counter) {
            counter.textContent = ta.value.length;
            ta.addEventListener('input', function () {
                counter.textContent = this.value.length;
            });
        }
    });

    // ── Auto-dismiss flashes after 6 seconds ─────────────────────
    document.querySelectorAll('.flash-success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';

            setTimeout(function () {
                el.remove();
            }, 400);
        }, 6000);
    });

    // ── User profile dropdown ────────────────────────────────────
    var userProfileTrigger = document.getElementById('userProfileTrigger');
    var userProfileMenu = document.getElementById('userProfileMenu');

    function closeUserProfileMenu() {
        if (userProfileMenu && userProfileTrigger) {
            userProfileMenu.classList.remove('open');
            userProfileTrigger.setAttribute('aria-expanded', 'false');
        }
    }

    if (userProfileTrigger && userProfileMenu) {
        userProfileTrigger.addEventListener('click', function (e) {
            e.stopPropagation();

            var isOpen = userProfileMenu.classList.toggle('open');
            userProfileTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!userProfileMenu.contains(e.target) && !userProfileTrigger.contains(e.target)) {
                closeUserProfileMenu();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeUserProfileMenu();
            }
        });
    }

    // ── Logout confirmation ──────────────────────────────────────
    document.querySelectorAll('.js-logout-confirm').forEach(function (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            var confirmed = window.confirm('Are you sure you want to logout?');

            if (!confirmed) {
                e.preventDefault();
            }
        });
    });

    // ── Admin sidebar toggle ─────────────────────────────────────
    var adminSidebarToggle = document.getElementById('adminSidebarToggle');
    var adminSidebarClose = document.getElementById('adminSidebarClose');
    var adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');
    var adminSidebarStorageKey = 'eventhub_admin_sidebar_collapsed';

    if (adminSidebarToggle) {
        var adminIsMobile = function () {
            return window.innerWidth <= 980;
        };

        var applyAdminSidebarState = function (collapsed) {
            body.classList.toggle('sidebar-collapsed', collapsed);
            adminSidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        };

        var savedAdminSidebarState = localStorage.getItem(adminSidebarStorageKey);

        if (savedAdminSidebarState === '1') {
            applyAdminSidebarState(true);
        } else if (savedAdminSidebarState === '0') {
            applyAdminSidebarState(false);
        } else {
            applyAdminSidebarState(adminIsMobile());
        }

        adminSidebarToggle.addEventListener('click', function () {
            var collapsed = !body.classList.contains('sidebar-collapsed');
            applyAdminSidebarState(collapsed);
            localStorage.setItem(adminSidebarStorageKey, collapsed ? '1' : '0');
        });

        if (adminSidebarClose) {
            adminSidebarClose.addEventListener('click', function () {
                applyAdminSidebarState(true);
                localStorage.setItem(adminSidebarStorageKey, '1');
            });
        }

        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.addEventListener('click', function () {
                applyAdminSidebarState(true);
                localStorage.setItem(adminSidebarStorageKey, '1');
            });
        }

        window.addEventListener('resize', function () {
            if (adminIsMobile() && !body.classList.contains('sidebar-collapsed')) {
                adminSidebarToggle.setAttribute('aria-expanded', 'true');
            }
        });
    }

    // ── User sidebar toggle ──────────────────────────────────────
    var userSidebarToggle = document.getElementById('userSidebarToggle');
    var userSidebarClose = document.getElementById('userSidebarClose');
    var userSidebarBackdrop = document.getElementById('userSidebarBackdrop');
    var userSidebarStorageKey = 'eventhub_user_sidebar_collapsed';

    if (userSidebarToggle) {
        var userIsMobile = function () {
            return window.innerWidth <= 980;
        };

        var applyUserSidebarState = function (collapsed) {
            body.classList.toggle('user-sidebar-collapsed', collapsed);
            userSidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

            if (collapsed) {
                closeUserProfileMenu();
            }
        };

        var savedUserSidebarState = localStorage.getItem(userSidebarStorageKey);

        if (savedUserSidebarState === '1') {
            applyUserSidebarState(true);
        } else if (savedUserSidebarState === '0') {
            applyUserSidebarState(false);
        } else {
            applyUserSidebarState(false);
        }

        userSidebarToggle.addEventListener('click', function () {
            var collapsed = !body.classList.contains('user-sidebar-collapsed');
            applyUserSidebarState(collapsed);
            localStorage.setItem(userSidebarStorageKey, collapsed ? '1' : '0');
        });

        if (userSidebarClose) {
            userSidebarClose.addEventListener('click', function () {
                applyUserSidebarState(true);
                localStorage.setItem(userSidebarStorageKey, '1');
            });
        }

        if (userSidebarBackdrop) {
            userSidebarBackdrop.addEventListener('click', function () {
                applyUserSidebarState(true);
                localStorage.setItem(userSidebarStorageKey, '1');
            });
        }

        window.addEventListener('resize', function () {
            if (userIsMobile() && !body.classList.contains('user-sidebar-collapsed')) {
                userSidebarToggle.setAttribute('aria-expanded', 'true');
            }
        });
    }
});

document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = btn.parentElement.querySelector('input');
        var eyeOpen = btn.querySelector('.eye-open');
        var eyeClosed = btn.querySelector('.eye-closed');

        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'inline-flex';
            btn.setAttribute('aria-label', 'Hide password');
            btn.setAttribute('title', 'Hide password');
        } else {
            input.type = 'password';
            eyeOpen.style.display = 'inline-flex';
            eyeClosed.style.display = 'none';
            btn.setAttribute('aria-label', 'Show password');
            btn.setAttribute('title', 'Show password');
        }
    });
});

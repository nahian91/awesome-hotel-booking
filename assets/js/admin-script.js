(function($) {
    'use strict';

    /**
     * AHBN Integrated Application Controller
     * A unified suite for Fleet, CRM, Branding, and Payments.
     */
    const acrbApp = {
        
        /**
         * 1. INITIALIZATION
         */
        init: function() {
            // Setup localized strings & data with fallbacks
            this.vars = window.acrbVars || {};
            this.editorVars = window.acrbEditorStrings || { frameTitle: "Select Images" };
            
            this.galleryFrame = null;
            this.singleFrame = null;

            this.cacheDOM();
            this.bindEvents();
            
            // Initialize dynamic components
            this.initDataTables();
            this.initRepeaters();
            this.initIconPicker();
            this.initCharts();
            this.calculateTotal(); 

            console.log('AHBN: Full System Core Initialized.');
        },

        /**
         * 2. DOM CACHING
         */
        cacheDOM: function() {
            this.$body           = $('body');
            this.$toast          = $('#ahbn-toast');
            
            // Branding Elements
            this.$logoUrlInput   = $('#ahbn_logo_url');
            this.$logoPreview    = $('#preview-logo-container');
            this.$accentPicker   = $('#ahbn_accent_picker');
            this.$footerInput    = $('#ahbn_footer_input');

            // Media Elements
            this.$idsField       = $('#ahbn_gallery_ids');
            this.$previewArea    = $('#ahbn_gallery_preview');
            this.$uploadGallery  = $('#ahbn_upload_gallery');
            this.$typeImageInput = $('#ahbn_type_image_id');

            // Calculation Elements
            this.$daysInput      = $('#ahbn_days');
            this.$rateInput      = $('#ahbn_rate');
            this.$totalDisplay   = $('#ahbn_calc_row');
            this.$totalInput     = $('#ahbn_total_amount');
        },

        /**
         * 3. EVENT BINDING
         */
        bindEvents: function() {
            // --- Branding & Live Sync ---
            this.$body.on('click', '#ahbn_upload_btn', (e) => this.handleLogoUpload(e));
            
            this.$accentPicker.on('input', (e) => {
                $('#preview-header, #preview-btn').css('background-color', $(e.target).val());
            });

            this.$footerInput.on('keyup', (e) => {
                $('#preview-footer').text($(e.target).val());
            });

            this.$logoUrlInput.on('change blur', () => this.syncLogoPreview());

            // --- Payment Gateways ---
            this.$body.on('change', '.ahbn-gateway-toggle', function() {
                $(this).closest('.ahbn-method-card').toggleClass('is-active', this.checked);
            });

            // --- Media Handlers ---
            if (this.$uploadGallery.length) {
                this.$uploadGallery.on('click', (e) => this.handleGalleryUpload(e));
                this.$previewArea.on('click', '.ahbn-gallery-remove', (e) => this.handleGalleryRemove(e));
            }
            this.$body.on('click', '#ahbn_type_dropzone', (e) => this.handleSingleImageUpload(e));

            // --- Repeaters & Calculations ---
            this.$body.on('click', '.ahbn-add-link', (e) => this.handleAddRepeaterRow(e));
            this.$body.on('click', '.ahbn-remove-btn', (e) => this.handleRemoveRepeaterRow(e));
            
            if (this.$daysInput.length) {
                this.$daysInput.add(this.$rateInput).on('input change', () => this.calculateTotal());
            }

            // --- Global UI ---
            this.$body.on('click', '.js-ahbn-save', (e) => this.handleSave(e));
            this.$body.on('click', '.js-ahbn-copy', (e) => this.handleCopy(e));
            this.$body.on('click', '.ahbn-delete-confirm', (e) => this.handleDelete(e));
        },

        /**
         * 4. DATA TABLES (Fleet & CRM)
         */
        initDataTables: function() {
            if (!$.fn.DataTable) return;

            const sharedConfig = { 
                pageLength: 15, 
                responsive: true, 
                dom: '<"ahbn-table-top"f>rt<"ahbn-table-bottom"ip><"clear">' 
            };

            const definitions = [
                { id: '#ahbn-cars-table', config: { order: [[1, 'asc']], columnDefs: [{ targets: [0, 5], orderable: false }] }},
                { id: '#ahbn-bookings-table', config: { order: [[0, "desc"]] }},
                { id: '#crm-table', config: { pageLength: 10, order: [[2, "desc"]] }},
                { id: '#history-table', config: { dom: 'rtp', pageLength: 8 }}
            ];

            definitions.forEach(table => {
                const $el = $(table.id);
                if ($el.length) {
                    if ($.fn.DataTable.isDataTable($el)) $el.DataTable().destroy();
                    $el.DataTable($.extend(true, {}, sharedConfig, table.config));
                }
            });
        },

        /**
         * 5. ANALYTICS (Charts)
         */
        initCharts: function() {
            if (typeof Chart === 'undefined') return;

            const revenueCtx = document.getElementById('acrbRevenueChart');
            if (revenueCtx && window.acrbLineData) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: Object.keys(window.acrbLineData),
                        datasets: [{
                            label: 'Revenue',
                            data: Object.values(window.acrbLineData),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            }
        },

        /**
         * 6. BRANDING & MEDIA LOGIC
         */
        handleLogoUpload: function(e) {
            e.preventDefault();
            const frame = wp.media({ title: 'Select Brand Logo', multiple: false }).open();
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                this.$logoUrlInput.val(attachment.url);
                this.syncLogoPreview();
            });
        },

        syncLogoPreview: function() {
            const url = this.$logoUrlInput.val();
            this.$logoPreview.html(url ? `<img src="${url}" id="preview-logo">` : `<span id="preview-logo-placeholder">YOUR LOGO</span>`);
        },

        handleSingleImageUpload: function(e) {
            e.preventDefault();
            if (this.singleFrame) { this.singleFrame.open(); return; }
            this.singleFrame = wp.media({ title: 'Select Image', button: { text: 'Use Image' }, multiple: false });
            this.singleFrame.on('select', () => {
                const data = this.singleFrame.state().get('selection').first().toJSON();
                this.$typeImageInput.val(data.id);
                $('#ahbn_type_preview').html(`<img src="${data.url}" class="ahbn-preview-img">`);
            });
            this.singleFrame.open();
        },

        handleGalleryUpload: function(e) {
            e.preventDefault();
            if (this.galleryFrame) { this.galleryFrame.open(); return; }
            this.galleryFrame = wp.media({ title: this.editorVars.frameTitle, multiple: true });
            this.galleryFrame.on('select', () => {
                const selection = this.galleryFrame.state().get('selection');
                let ids = this.$idsField.val() ? this.$idsField.val().split(',') : [];
                selection.map((attachment) => {
                    const data = attachment.toJSON();
                    if ($.inArray(data.id.toString(), ids) === -1) {
                        ids.push(data.id.toString());
                        this.$previewArea.append(`<div class="ahbn-gallery-item" data-id="${data.id}"><img src="${data.url}"><span class="ahbn-gallery-remove">×</span></div>`);
                    }
                });
                this.$idsField.val(ids.join(','));
            });
            this.galleryFrame.open();
        },

        handleGalleryRemove: function(e) {
            const $item = $(e.currentTarget).parent();
            let ids = this.$idsField.val().split(',');
            ids = $.grep(ids, (val) => val !== $item.data('id').toString());
            this.$idsField.val(ids.join(','));
            $item.fadeOut(300, function() { $(this).remove(); });
        },

        /**
         * 7. REPEATERS & ICON PICKER
         */
        initRepeaters: function() {
            this.repeaterDefaults = { features: 'dashicons-car', amenities: 'dashicons-star-filled', locations: 'dashicons-location' };
        },

        handleAddRepeaterRow: function(e) {
            const $btn = $(e.currentTarget);
            const target = $btn.data('target');
            const type = $btn.data('type');
            const $container = $('#' + target);
            const idx = Date.now();
            let defaultIcon = this.repeaterDefaults[type] || 'dashicons-admin-generic';

            $container.find('.ahbn-empty-msg').remove();
            let fieldTemplate = (type === 'features') 
                ? `<input type="text" name="${type}[${idx}][name]" class="ahbn-field ahbn-w-130" placeholder="Label"><input type="text" name="${type}[${idx}][value]" class="ahbn-field ahbn-flex-1" placeholder="Value">`
                : `<input type="text" name="${type}[${idx}][name]" class="ahbn-field ahbn-flex-1" placeholder="Name">`;

            const iconGridHtml = $('.ahbn-icon-dropdown').first().find('.ahbn-icon-grid').html() || '';
            const html = `
                <div class="ahbn-row" style="display:none;">
                    <div class="ahbn-icon-box"><span class="dashicons ${defaultIcon}"></span></div>
                    <input type="hidden" name="${type}[${idx}][icon]" value="${defaultIcon}" class="ahbn-icon-val">
                    ${fieldTemplate}
                    <span class="dashicons dashicons-no-alt ahbn-remove-btn"></span>
                    <div class="ahbn-icon-dropdown"><div class="ahbn-icon-grid">${iconGridHtml}</div></div>
                </div>`;
            
            $(html).appendTo($container).fadeIn(200);
        },

        handleRemoveRepeaterRow: function(e) {
            const $row = $(e.currentTarget).closest('.ahbn-row');
            const $container = $row.closest('.ahbn-row-container');
            $row.fadeOut(200, function() { 
                $(this).remove(); 
                if ($container.find('.ahbn-row').length === 0) $container.append('<div class="ahbn-empty-msg">No items added.</div>');
            });
        },

        initIconPicker: function() {
            this.$body.on('click', '.ahbn-icon-box', (e) => {
                e.stopPropagation();
                const $drop = $(e.currentTarget).siblings('.ahbn-icon-dropdown');
                $('.ahbn-icon-dropdown').not($drop).removeClass('is-open');
                $drop.toggleClass('is-open');
            });

            this.$body.on('click', '.ahbn-icon-opt', (e) => {
                const $opt = $(e.currentTarget);
                const $row = $opt.closest('.ahbn-row');
                $row.find('.ahbn-icon-val').val($opt.data('icon'));
                $row.find('.ahbn-icon-box span').attr('class', 'dashicons ' + $opt.data('icon'));
                $('.ahbn-icon-dropdown').removeClass('is-open');
            });

            $(document).on('click', () => $('.ahbn-icon-dropdown').removeClass('is-open'));
        },

        /**
         * 8. UTILITIES
         */
        calculateTotal: function() {
            const days = parseFloat(this.$daysInput.val()) || 0;
            const rate = parseFloat(this.$rateInput.val()) || 0;
            const subtotal = (days * rate).toFixed(2);
            
            this.$totalDisplay.text(subtotal);
            this.$totalInput.val(subtotal);
        },

        handleCopy: function(e) {
            const $btn = $(e.currentTarget);
            const targetId = $btn.data('target');
            const $input = $(`#${targetId}`);
            
            // Get text from input value or fallback to element text
            const textToCopy = $input.length ? $input.val() : $btn.text().trim();

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    this.showToast();
                });
            } else {
                // Fallback for non-https or older browsers
                if ($input.length) {
                    $input.select();
                    document.execCommand('copy');
                }
                this.showToast();
            }
            
            $btn.addClass('is-copied');
            setTimeout(() => $btn.removeClass('is-copied'), 500);
        },

        handleSave: function(e) {
            const $btn = $(e.currentTarget);
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving...');
            $('.ahbn-content-card form').trigger('submit');
        },

        handleDelete: function(e) {
            if (!window.confirm(this.vars.confirmDelete || "Are you sure?")) {
                e.preventDefault();
            }
        },

        showToast: function() {
            this.$toast.addClass('is-active');
            setTimeout(() => this.$toast.removeClass('is-active'), 2000);
        }
    };

    // Run on Doc Ready
    $(function() { 
        acrbApp.init(); 
    });

})(jQuery);
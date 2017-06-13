"use strict";

define([
        'pim/form',
        'text!oro/template/system/group/symcodecloud',
        'bootstrap.bootstrapswitch'
    ],
    function(
        BaseForm,
        template
    ) {
        return BaseForm.extend({
            className: 'tab-pane',
            events: {
                'change input': 'updateModel'
            },
            isGroup: true,
            label: 'Symcode Cloud',
            template: _.template(template),

            /**
             * {@inheritdoc}
             */
            render: function () {
                var formData = this.getFormData();
                console.debug(formData);
                this.$el.html(this.template({
                    'active': formData['symcode_cloud_akeneo___active'].value,
                    'callback_active': formData['symcode_cloud_akeneo___callback_active'].value,
                    'callback_pattern': formData['symcode_cloud_akeneo___callback_pattern'].value,
                    'host': formData['symcode_cloud_akeneo___host'].value,
                    'username': formData['symcode_cloud_akeneo___username'].value,
                    'password': formData['symcode_cloud_akeneo___password'].value
                }));

                this.$('.switch').bootstrapSwitch();

                this.delegateEvents();

                return BaseForm.prototype.render.apply(this, arguments);
            },

            /**
             * Update model after value change
             *
             * @param {Event}
             */
            updateModel: function (event) {
                var data = this.getFormData();
                var id = $(event.target).prop("id");
                if(typeof data[id] === "undefined" || !data[id]){
                    data[id] = {};
                }
                if($(event.target).prop('type') == "checkbox"){
                    data[id].value = $(event.target).prop('checked') ? '1' : '0';
                } else {
                    data[id].value = $(event.target).val();
                }
                this.setData(data);
            }
        });
    }
);

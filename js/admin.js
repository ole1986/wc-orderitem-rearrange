/**
 * WcOrderItemRearrange class
 */
function WcOrderItemRearrangeClass() {
    var $ = jQuery;
    var self = this;

    /**
     * 
     * @param {JSON} data data parameters
     * @param {String} action action
     */
    var jsonRequest = function (data, action) {
        if(!action) {
            alert('No hook action defined');
            return;
        }
        $.extend(data, { action: action });
        return jQuery.post(ajaxurl, data, null, 'json');
    };

    this.MoveOrderItem = function (e, item_id, direction) {
        event.preventDefault();
        jsonRequest({ item_id, direction}, 'OrderItemRearrange').done(function(resp) {
            jQuery( '#woocommerce-order-items .cancel-action').attr('data-reload', true)
            jQuery( '#woocommerce-order-items .cancel-action').trigger('click');
        });
    }
}

window['WcOrderItemRearrange'] = new WcOrderItemRearrangeClass();
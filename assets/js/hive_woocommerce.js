; (function ($) {

    $(document).on('click', '.woocommerce-pagination a', function (e) {
        e.preventDefault();

        //Make wordpress ajax
        var $this = $(this);
        var $parent = $this.closest('.woocommerce-pagination');
        var $ajaxurl = hive_woocommerce_ajax_object.ajax_url;
        var $paged = $(this).text();
        '&rarr;' == $paged ? $paged = hive_woocommerce_ajax_object.paged++ : $paged = $paged;
        '&larr;' == $paged ? $paged = hive_woocommerce_ajax_object.paged-- : $paged = $paged;
        hive_woocommerce_ajax_object.paged = $paged;

        $order = $('.woocommerce-ordering select.orderby').val();

        console.log($order);

        var $data = {
            paged: $(this).text(),
            orderby: $order,
            action: 'hive_woocommerce_pagination',
            security: hive_woocommerce_ajax_object.hive_woocommerce_nonce,
        };


        //Call the ajax
        $.post($ajaxurl, $data, function (response) {
            response = JSON.parse(response);
            $('nav.woocommerce-pagination').html(response.pagination);
            $('ul.products.columns-3').html(response.html);
            $('.woocommerce-result-count').remove();
        }), 'json';

    }

    );

    $(document).ready(function () {
        $(".woocommerce-ordering").off("change", "select.orderby").on("change", "select.orderby", function (e) {
            e.preventDefault();
            var $order = $(this).val();
            var $ajaxurl = hive_woocommerce_ajax_object.ajax_url;
            var $data = {
                orderby: $order,
                action: 'hive_woocommerce_pagination',
                security: hive_woocommerce_ajax_object.hive_woocommerce_nonce,
            };

            //Call the ajax
            $.post($ajaxurl, $data, function (response) {
                response = JSON.parse(response);
                $('nav.woocommerce-pagination.woocommerce-pagination').html(response.pagination);
                $('ul.products.columns-3').html(response.html);
                $('.woocommerce-result-count').remove();
            }), 'json';

        });




    });







})(jQuery);
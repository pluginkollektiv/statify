<?php


/* Quit */
class_exists('Statify') OR exit; ?>


<!-- Stats by http://statify.de -->
<script type="text/javascript">
    (function() {
        var e = document.createElement('script'),
            s = document.getElementsByTagName('script')[0],
            r = encodeURIComponent(document.referrer),
            t = encodeURIComponent(location.pathname + location.search),
            p = '?statify_referrer=' + r + '&statify_target=' + t;

        e.async = true;
        e.type = 'text/javascript';
        e.src = "<?php echo home_url('/', 'relative'); ?>" + p;

        s.parentNode.insertBefore(e, s);
    })();
</script>


<?php /* Markup space */ ?>
<script>
    //Perform some actions when window is ready
    jQuery(window).load(function () {
        //Setup tooltipster
        jQuery('.gkit-tooltip').tooltipster({
            contentAsHTML: true,
            animation: 'fade', //fade, grow, swing, slide, fall
            theme: 'tooltipster-shadow',
            position: 'bottom'
        });
    });
</script>

jQuery(document).ready(function($){
     $(".emd-vid").click(function() {
         var lastdiv = $(".item.active");
         var lasthtml = $(".item.active").html();
         lastdiv.html('');
         lastdiv.html(lasthtml);
    });
});
var total_loaded = 0;
jQuery(document).ready(function($){
	$("#clerk-show-facets").click(function() {
	    $("#clerk-facets-container").toggle(300);
        $('.clerk-toggle-sort').slideToggle(300);
    });

	Clerk('on','rendered',function(){
		$(".clerk-facet-search").attr("placeholder","Zoeken");
		$(".clerk-facet-show-more").text("Laad Meer");
	})
					
    Clerk('on', 'rendered', function(content, data) {
        total_loaded += data.response.result.length;
        var e = jQuery('#clerk-search');
        if (typeof e.data('limit') === "undefined") {
            e.data('limit', data.response.result.length)
        }
        if (total_loaded == 0) {
            jQuery('#clerk-search-no-results').show();
        } else {
            jQuery('#clerk-search-no-results').hide();
        }
    });
});

document.addEventListener("DOMContentLoaded", function(){
    //Sorting
    $('select.clerk-result-sort').on("change",function(){
        if($(this).val()=='relevance'){
            Clerk('content','#clerk-search', 'param', {
                orderby: null,
                order: null
            });
        } 
        else if ($(this).val()=='upprice'){
            Clerk('content','#clerk-search', 'param', {
                orderby: 'price',
                order: 'asc'
            });
        } 
        else if ($(this).val()=='downprice'){
            Clerk('content','#clerk-search', 'param', {
                orderby: 'price',
                order: 'desc'
            });
        } 
        else if ($(this).val()=='upname'){
            Clerk('content','#clerk-search', 'param', {
                orderby: 'name',
                order: 'asc'
            });
        } 
        else if ($(this).val()=='downname'){
            Clerk('content','#clerk-search', 'param', {
                orderby: 'name',
                order: 'desc'
            });
        } 
        else if ($(this).val()=='downage'){
            Clerk('content','#clerk-search', 'param', {
                orderby: 'created_at',
                order: 'asc'
            });
        } 
        else if ($(this).val()=='upage'){
            Clerk('content','#clerk-search', 'param', {
                orderby: 'created_at',
                order: 'desc'
            });
        }
    });
});
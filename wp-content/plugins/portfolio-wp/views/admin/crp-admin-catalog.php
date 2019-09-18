<?php

$crp_pid = 0;

if(isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])){
    $crp_action = 'edit';
    $crp_pid = (int)$_GET['id'];
}else if(isset($_GET['action']) && $_GET['action'] === 'create'){
    $crp_action = 'create';
}

?>

<div class="crp-portfolio-header">

    <div class="crp-three-parts crp-fl">
        <a class='button-secondary portfolio-button crp-glazzed-btn crp-glazzed-btn-dark' href="<?php echo "?page={$crp_adminPage}"; ?>">
            <div class='crp-icon crp-portfolio-button-icon'><i class="fa fa-long-arrow-left"></i></div>
        </a>
    </div>

    <div class="crp-three-parts crp-fl crp-title-part"><input id="crp-portfolio-title" class="crp-portfolio-title" name="portfolio-title" maxlength="250" placeholder="Enter catalog title" type="text"></div>

    <div class="crp-three-parts crp-fr">
        <a id="crp-save-portfolio-button" class='button-secondary portfolio-button crp-glazzed-btn crp-glazzed-btn-green crp-fr' href="#">
            <div class='crp-icon crp-portfolio-button-icon'><i class="fa fa-save fa-fw"></i></div>
        </a>
        <a id="crp-portfolio-options-button" class='button-secondary portfolio-button crp-glazzed-btn crp-glazzed-btn-orange crp-fr' href="#" onclick="onPortfolioOptions()">
            <div class='crp-icon crp-portfolio-button-icon'><i class="fa fa-cog fa-fw"></i></div>
        </a>
    </div>
</div>

<hr />

<div class="crp-empty-project-list-alert">
    <h3>You don't have items yet!</h3>
</div>

<div class="crp-project-wrapper crp-catalog-wrapper">
    <aside class="crp-project-sidebar">
        <div>
            <a id="crp-add-project-button" class='button-secondary crp-add-project-button crp-glazzed-btn crp-glazzed-btn-green' href='#' title='Add new'>+ New product</a>
        </div>

        <ul id="crp-project-list" class="crp-project-list handles list">
        </ul>
    </aside>
    <section class="crp-project-preview-wrapper">
        <div class="crp-project-details-wrapper">
            <aside class="crp-project-details-sidebar">
                <div id="crp-project-details-content">
                    <input id="crp-project-title" class="crp-project-title" name="project.title" value="" type="text" placeholder="Enter product name">

                    <div id="crp-project-cover-img" class="crp-project-cover-img">
                        <div id="crp-project-cover-img-overlay">
                            <div id="crp-project-cover-img-overlay-content">
                                <div class='crp-icon crp-edit-icon crp-edit-project-cover-icon'> </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="crp-project-cover-src" name="project.cover" value="" />

                    <textarea id="crp-project-description" name="project.description" placeholder="Enter product description..."></textarea>
                    <input id="crp-project-url" name="project.url" value="" type="text" placeholder="Enter checkout url">
                    <input class="crp-fullwidth-input" id="crp-project-price" name="project.price" value="" type="text" placeholder="Enter product price">
                    <input class="crp-fullwidth-input" id="crp-project-sale" name="project.sale" value="" type="text" placeholder="Enter product sale (%)">
                </div>
            </aside>
            <section class="crp-project-images-wrapper">
                <div class="crp-add-picture-button-wrapper">
                    <a id="crp-add-picture-button" class='button-secondary crp-add-picture-button crp-glazzed-btn crp-glazzed-btn-green' href='#' title='Add new'>+ Add picture</a>
<!--                    <a id="crp-add-video-button" class='button-secondary crp-add-picture-button crp-glazzed-btn crp-glazzed-btn-green gkit-tooltip' href='#' title='--><?php //echo htmlentities('<div class="crp-tooltip-content">Upgrade to Premium version for Local videos</div>'); ?><!--'>+ Video (PRO)</a>-->
<!--                    <a id="crp-add-youtube-button" class='button-secondary crp-add-picture-button crp-glazzed-btn crp-glazzed-btn-green gkit-tooltip' href='#' title='--><?php //echo htmlentities('<div class="crp-tooltip-content">Upgrade to Premium version for Youtube videos</div>'); ?><!--'>+ Youtube (PRO)</a>-->
<!--                    <a id="crp-add-vimeo-button" class='button-secondary crp-add-picture-button crp-glazzed-btn crp-glazzed-btn-green gkit-tooltip' href='#' title='--><?php //echo htmlentities('<div class="crp-tooltip-content">Upgrade to Premium version for Vimeo videos</div>'); ?><!--'>+ Vimeo (PRO)</a>-->
<!--                    <a id="crp-add-iframe-button" class='button-secondary crp-add-picture-button crp-glazzed-btn crp-glazzed-btn-green gkit-tooltip' href='#' title='--><?php //echo htmlentities('<div class="crp-tooltip-content">Upgrade to Premium version for iFrames</div>'); ?><!--'>+ Iframe (PRO)</a>-->
<!--                    <a id="crp-add-map-button" class='button-secondary crp-add-picture-button crp-glazzed-btn crp-glazzed-btn-green gkit-tooltip' href='#' title='--><?php //echo htmlentities('<div class="crp-tooltip-content">Upgrade to Premium version for Maps</div>'); ?><!--'>+ Map (PRO)</a>-->
                </div>

                <ul id="crp-project-images-grid" class="crp-project-images-grid sortable grid" style="overflow-y: auto">
                </ul>
            </section>
        </div>
    </section>
</div>

<script>

//Show loading while the page is being complete loaded
crp_showSpinner();

//Configure javascript vars passed PHP
var crp_adminPage = "<?php echo $crp_adminPage ?>";
var crp_action = "<?php echo $crp_action ?>";
var crp_selectedProjectId = 0;

var crp_categoryAutocompleteDS = [];

//Configure portfolio model
var crp_portfolio = {};
crp_portfolio.id = "<?php echo $crp_pid ?>";
crp_portfolio.projects = {};
crp_portfolio.corder = [];
crp_portfolio.deletions = [];
crp_portfolio.isDraft = true;

jQuery(".crp-project-preview-wrapper").hide();
jQuery(".crp-empty-project-list-alert").show();

//Perform some actions when window is ready
jQuery(window).load(function () {
    //Setup sortable lists and grids
    jQuery('.sortable').sortable();
    jQuery('.handles').sortable({
//        handle: 'span'
    });
    jQuery('#crp-project-list').sortable().bind('sortupdate', function(e, ui) {
        //ui.item contains the current dragged element.
        //Triggered when the user stopped sorting and the DOM position has changed.
        crp_updateModel();
    });


    jQuery('#crp-project-categories').tagEditor({
        placeholder: "Enter comma separated categories",
    });

    //In case of edit we sould perform ajax call and retrieve the specified portfolio for editing
    if(crp_action == 'edit'){
        crp_portfolio = crpAjaxGetPortfolioWithId(crp_portfolio.id);

        //NOTE: The validation and moderation is very important thing. Here could be not expected conversion
        //from PHP to Javascript JSON objects. So here we will validate, if needed we will do changes
        //to meet our needs
        crp_portfolio = validatedPortfolio(crp_portfolio);
        //This portfolio is already exists on server, so it's not draft item
        crp_portfolio.isDraft = false;
    }

    jQuery( '#crp-project-cover-img' ).on( 'click', function( evt ) {
        // Stop the anchor's default behavior
        evt.preventDefault();

        // Display the media uploader
        crp_openMediaUploader( function callback(picInfo){
             changeProjectCover(picInfo);
        }, false );
    });

    jQuery("#crp-add-project-button").on( 'click', function( evt ){
        evt.preventDefault();

        //Keep all the changes
        crp_updateModel();

        //Create new draft project
        var crp_project = {};
        crp_project.id = crp_generateId();
        crp_project.isDraft = true;
        crp_project.categories = [];

        crp_portfolio.projects[crp_project.id] = crp_project;
        crp_portfolio.corder.unshift(crp_project.id);

        //Set it as selected
        crp_selectedProjectId = crp_project.id;

        //Update UI
        crp_updateUI();
        jQuery("#crp-project-list").scrollTop(0);
    });

    jQuery( "#crp-project-list" ).live('click', function(event) {

        var crp_targetElement = null;
        if(jQuery(event.target).hasClass('crp-project-li')){
            crp_targetElement = event.target;
        }else if (jQuery(event.target).hasClass('crp-project-title-label')){
            crp_targetElement = jQuery(event.target).parent();
        }else{
            return;
        }

        var crp_projId = jQuery(crp_targetElement).attr('id');
        if(crp_projId != crp_selectedProjectId){
            crp_updateModel();

            crp_selectedProjectId = crp_projId;
            var _curScrollPos = jQuery("#crp-project-list").scrollTop();

            crp_updateUI();
            jQuery("#crp-project-list").scrollTop(_curScrollPos);
        }
    });


    jQuery("#crp-save-portfolio-button").on( 'click', function( evt ){
        evt.preventDefault();

        //Apply last changes to the model
        crp_updateModel();

        //Validate saving
        var crp_activeProject = crp_portfolio.projects[crp_selectedProjectId];
        if(!crp_portfolio.title){
            alert("Oops! You're trying to save a project without title.");
            return;
        }

        //Show spinner
        crp_showSpinner();

        //Perform Ajax calls
        crp_result = crpAjaxSavePortfolio(crp_portfolio);

        //Get updated model from the server
        crp_portfolio = crpAjaxGetPortfolioWithId(crp_result['pid']);
        crp_portfolio = validatedPortfolio(crp_portfolio);
        crp_portfolio.isDraft = false;

        crp_selectedProjectId = 0;

        //Update UI
        crp_updateUI();
        jQuery("#crp-project-list").scrollTop(0);

        //Hide spinner
        crp_hideSpinner();
    });

    jQuery("#crp-add-picture-button").on( 'click', function( evt ){
        evt.preventDefault();

        crp_openMediaUploader( function callback(picInfoArr){
            if(picInfoArr && picInfoArr.length > 0)

            for(var pi = 0; pi < picInfoArr.length; pi++){
                var picInfo = picInfoArr[pi];
                var crp_picId = crp_generateId();

                var innerHTML = "";
                innerHTML +=    "<li id='" + crp_picId + "' class = 'crp-pic-li'>";
                innerHTML +=        "<div id='crp-project-pic-" + crp_picId + "' class='crp-project-pic'>";
                innerHTML +=            "<div class='crp-project-pic-overlay'>";
                innerHTML +=                "<div class='crp-project-pic-overlay-content'>";
                innerHTML +=                    "<div class='crp-icon crp-trash-icon crp-trash-project-pic-icon' onClick='onDeleteProjectPic(\"" + crp_picId + "\")'> </div>";
                innerHTML +=                    "<div class='crp-icon crp-edit-icon crp-edit-project-pic-icon' onClick='onEditProjectPic(\"" + crp_picId + "\")'> </div>";
                innerHTML +=                "</div>";
                innerHTML +=            "</div>"
                innerHTML +=         "</div>"
                innerHTML +=         "<input type='hidden' id='crp-project-pic-src-" + crp_picId + "' value='' />";
                innerHTML +=    "</li>";

                jQuery("#crp-project-images-grid").append( innerHTML );
                changeProjectPic(crp_picId, picInfo);
            }
        }, true );

        jQuery("#crp-project-images-grid").scrollTop(0);
    });

    jQuery(document).keypress(function(event) {
        //cmd+s or control+s
        if (event.which == 115 && (event.ctrlKey||event.metaKey)|| (event.which == 19)) {
            event.preventDefault();

            jQuery( "#crp-save-portfolio-button" ).trigger( "click" );
            return false;
        }
        return true;
    });

    //Update UI based on retrieved/(just create) model
    crp_updateUI();

    //When the page is ready, hide loading spinner
    crp_hideSpinner();
});

function crp_updateUI(){

    if(crp_portfolio.title){
        jQuery("#crp-portfolio-title").attr( "value", crp_portfolio.title );
    }


    jQuery(".crp-project-preview-wrapper").hide();
    jQuery(".crp-empty-project-list-alert").show();

    jQuery("#crp-project-list").empty();
    if(crp_portfolio.projects && crp_portfolio.corder){
        for(var crp_projectIndex = 0; crp_projectIndex < crp_portfolio.corder.length; crp_projectIndex++){

            var crp_projectId = crp_portfolio.corder[crp_projectIndex];
            if(!crp_portfolio.projects[crp_projectId]){
                continue;
            }

            var crp_project = crp_portfolio.projects[crp_projectId];

            var proj_thumb = crp_project.cover ? JSON.parse(CrpBase64.decode(crp_project.cover)) : null;
            var emptyProjThumb = proj_thumb ? '' : 'height: 30px;';
            var thumb_img = '/general/glazzed-image-placeholder-thumb.png';
            proj_thumb = proj_thumb ? proj_thumb.src : CRP_IMAGES_URL + thumb_img;

            var innerHTML = "";
            innerHTML += "<li id='" + crp_project.id +"' class = 'crp-project-li'>";
            innerHTML +=    "<span class = 'draggable'>:: </span>";
            innerHTML +=    '<div class="crp-proj-thumb" style="background-image: url('+proj_thumb+');'+emptyProjThumb+'"></div>';
            innerHTML +=    "<span class = 'crp-project-title-label'>" + crp_truncateIfNeeded( crp_project.title ? CrpBase64.decode(crp_project.title) : 'Untitled' , 18) + "</span>";
            innerHTML +=    "<div class='crp-icon crp-trash-icon crp-trash-project-icon' onClick='onDeleteProject(\"" + crp_project.id + "\")'> </div>";
            innerHTML += "</li>";
            jQuery("#crp-project-list").append( innerHTML );

            if(!crp_selectedProjectId){
                crp_selectedProjectId = crp_project.id;
            }

            if(crp_project.id == crp_selectedProjectId){
                jQuery("#" + crp_project.id + ".crp-project-li").addClass('active-project-li');

                //Update current project details view
                jQuery("#crp-project-title").attr( "value", (crp_project.title ? CrpBase64.decode(crp_project.title) : '') );
                jQuery("#crp-project-description").attr( "value", CrpBase64.decode(crp_project.description) );
                jQuery("#crp-project-url").attr( "value", (crp_project.url ? crp_project.url : '') );

                if (crp_project.details && typeof crp_project.details.price != 'undefined') {
                    jQuery("#crp-project-price").attr( "value", (crp_project.details.price ? crp_project.details.price : '') );
                } else {
                    jQuery("#crp-project-price").attr( "value", "");
                }
                if (crp_project.details && typeof crp_project.details.sale != 'undefined') {
                    jQuery("#crp-project-sale").attr( "value", (crp_project.details.sale ? crp_project.details.sale : '') );
                } else {
                    jQuery("#crp-project-sale").attr( "value", "");
                }

                changeProjectCover(crp_project.cover ? JSON.parse(CrpBase64.decode(crp_project.cover)) : null);

                jQuery("#crp-project-images-grid").empty();
                if(crp_project.pics){
                    crp_picInfoList = crp_project.pics.split(",");
                    for(var crp_picIndex=0; crp_picIndex<crp_picInfoList.length; crp_picIndex++){
                        if(!crp_picInfoList[crp_picIndex]) continue;

                        var crp_picId = crp_generateId();

                        var innerHTML = "";
                        innerHTML +=    "<li id='" + crp_picId + "' class = 'crp-pic-li'>";
                        innerHTML +=        "<div id='crp-project-pic-" + crp_picId + "' class='crp-project-pic'>";
                        innerHTML +=            "<div class='crp-project-pic-overlay'>";
                        innerHTML +=                "<div class='crp-project-pic-overlay-content'>";
                        innerHTML +=                    "<div class='crp-icon crp-trash-icon crp-trash-project-pic-icon' onClick='onDeleteProjectPic(\"" + crp_picId + "\")'> </div>";
                        innerHTML +=                    "<div class='crp-icon crp-edit-icon crp-edit-project-pic-icon' onClick='onEditProjectPic(\"" + crp_picId + "\")'> </div>";
                        innerHTML +=                "</div>";
                        innerHTML +=            "</div>";
                        innerHTML +=         "</div>";
                        innerHTML +=         "<input type='hidden' id='crp-project-pic-src-" + crp_picId + "' value='' />";
                        innerHTML +=    "</li>";

                        jQuery("#crp-project-images-grid").append( innerHTML );
                        changeProjectPic(crp_picId, JSON.parse(CrpBase64.decode(crp_picInfoList[crp_picIndex])));
                    }
                }

                jQuery("#crp-project-images-grid").scrollTop(0);

                jQuery(".crp-project-preview-wrapper").show();
                jQuery(".crp-empty-project-list-alert").hide();
            }
        }
    }
}

function crp_updateModel(){
  //To make sure it's valid JS object
    crp_portfolio = validatedPortfolio(crp_portfolio);

    crp_portfolio.title = jQuery("#crp-portfolio-title").attr( "value" );
    crp_portfolio.corder = jQuery("#crp-project-list").sortable("toArray");

    crp_portfolio.extoptions = {
        all_cats: {},
        type: '<?php echo CRPGridType::CATALOG; ?>'
    };

    if(crp_selectedProjectId){
        var crp_activeProject = crp_portfolio.projects[crp_selectedProjectId];

        crp_activeProject.title = CrpBase64.encode(jQuery("#crp-project-title").attr( "value" ));
        crp_activeProject.cover = CrpBase64.encode(jQuery("#crp-project-cover-src").attr( "value" ));
        crp_activeProject.description = CrpBase64.encode(jQuery("#crp-project-description").attr( "value" ));
        crp_activeProject.url = jQuery("#crp-project-url").attr( "value" );

        var details = {
            price: jQuery("#crp-project-price").val(),
            sale: jQuery("#crp-project-sale").val(),
            status: '<?php echo CRPProductStatus::Visible; ?>'
        };
        crp_activeProject.details = details;

        var crp_projectPics = "";
        var crp_picIDsList = jQuery("#crp-project-images-grid").sortable("toArray");
        for(var crp_picIndex = 0; crp_picIndex < crp_picIDsList.length; crp_picIndex++){
            var picInfo = jQuery("#crp-project-pic-src-" + crp_picIDsList[crp_picIndex]).attr( "value" );
            if(picInfo){
                crp_projectPics += CrpBase64.encode(picInfo) + ",";
            }
        }
        if(crp_projectPics.length > 0){
            crp_projectPics = crp_projectPics.substr(0,crp_projectPics.length-1); //Remove last ','
        }
        crp_activeProject.pics = crp_projectPics;

        crp_portfolio.projects[crp_selectedProjectId] = crp_activeProject;
    }
}

function validatedPortfolio(portfolio){
    if (!portfolio) {
      portfolio = {};
    }

    //NOTE: We use assoc array for projects, so if it's null/undefined or Array,
     //then we should change it as an Object to treat it as an assoc array
    if(!portfolio.projects || (portfolio.projects && crp_isJSArray(portfolio.projects))){
        portfolio.projects = {};
    }

    if(!portfolio.deletions || !(portfolio.deletions && crp_isJSArray(portfolio.deletions))){
        portfolio.deletions = [];
    }

    return portfolio;
}

function onEditProjectPic(crp_picId){
    crp_openMediaUploader( function callback(picInfo){
         changeProjectPic(crp_picId, picInfo);
    }, false );
}

function onDeleteProjectPic(crp_picId){
    jQuery("#"+ crp_picId + ".crp-pic-li").remove();
}

function onDeleteProject(crp_projectId){
    if(!crp_projectId) return;

    if(!confirm('Are you sure you want to delete the project?')) {
        return;
    }

    //Remove from projects assoc array and add in deletions list
    delete crp_portfolio.projects[crp_projectId];
    crp_portfolio.deletions.push(crp_projectId);

    //Remove from ordered list
    var crp_oi = crp_portfolio.corder.indexOf(crp_projectId);
    if(crp_oi >= 0){
        crp_portfolio.corder.splice(crp_oi,1);
    }

    var _curScrollPos = jQuery("#crp-project-list").scrollTop() - 40;

    //Set it as selected
    if(crp_selectedProjectId == crp_projectId){
        crp_selectedProjectId = 0;
        _curScrollPos = 0;
    }

    crp_updateUI();
    jQuery("#crp-project-list").scrollTop(_curScrollPos);
}

function onPortfolioOptions(){
    if(crp_portfolio.isDraft){
        alert("Save the draft portfolio before changing the view options");
    }else{
        var href = "?page=" + crp_adminPage + "&action=options&id=" + crp_portfolio.id;
        crp_loadHref(href);
    }
}

function changeProjectCover(picInfo){
    var thumb_img = '/general/glazzed-image-placeholder.png';
    // After that, set the properties of the image and display it
    jQuery( '#crp-project-cover-img' )
            .css( 'background', 'url(' + (picInfo ? picInfo.src : CRP_IMAGES_URL + thumb_img) + ') center no-repeat' )
            .css( 'background-size', 'cover');

    // Store the image's information into the meta data fields
    jQuery( '#crp-project-cover-src' ).val( JSON.stringify(picInfo) );
}

function changeProjectPic(crp_picId, picInfo){
    var thumb_img = '/general/glazzed-image-placeholder.png';
    // After that, set the properties of the image and display it
    jQuery( '#crp-project-pic-' + crp_picId )
            .css( 'background', 'url(' + (picInfo ? picInfo.src : CRP_IMAGES_URL + thumb_img) + ') center no-repeat' )
            .css( 'background-size', 'cover');

    // Store the image's information into the meta data fields
    jQuery( '#crp-project-pic-src-' + crp_picId ).val( JSON.stringify(picInfo) );
}

function htmlEntitiesEncode(str){
    return jQuery('<div/>').text(str).html();
}

</script>

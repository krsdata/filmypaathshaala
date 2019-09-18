<?php

$crp_pid = 0;

if(isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])){
    $crp_action = 'edit';
    $crp_pid = (int)$_GET['id'];
}else if(isset($_GET['action']) && $_GET['action'] === 'create'){
    $crp_action = 'create';
}

global $crp_theme;

?>

<div class="crp-portfolio-header">

    <div class="crp-three-parts crp-fl">
        <a class='button-secondary portfolio-button crp-glazzed-btn crp-glazzed-btn-dark' href="<?php echo "?page={$crp_adminPage}"; ?>">
            <div class='crp-icon crp-portfolio-button-icon'><i class="fa fa-long-arrow-left"></i></div>
        </a>
    </div>

    <div class="crp-three-parts crp-fl crp-title-part"><input id="crp-portfolio-title" class="crp-portfolio-title" name="portfolio-title" maxlength="250" placeholder="Enter clients name" type="text"></div>

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
    <h3>You don't have client logos in this gallery yet!</h3>
</div>

<div class="crp-gallery-wrapper crp-client-logos-wrapper">
    <div class="crp-add-item-boxes">
        <div class="crp-add-item-box"><a id="crp-add-picture-button" class='button-secondary crp-add-project-button crp-glazzed-btn crp-glazzed-btn-green' href='#' title='Add new client'>+ Add client</a></div>
    </div>

    <table id="crp-gallery-project-list">
    </table>
</div>

<script>

    var _CRP_LAST_GENERATED_INT_ID = 100000;
    function crp_generateIntId(){
        return ++_CRP_LAST_GENERATED_ID;
    }

    //Show loading while the page is being complete loaded
    crp_showSpinner();

    //Configure javascript vars passed PHP
    var crp_adminPage = "<?php echo $crp_adminPage ?>";
    var crp_action = "<?php echo $crp_action ?>";
    var crp_attachmentTypePicture = 'pic';

    //Configure portfolio model
    var crp_portfolio = {};
    crp_portfolio.id = "<?php echo $crp_pid ?>";
    crp_portfolio.projects = {};
    crp_portfolio.corder = [];
    crp_portfolio.deletions = [];
    crp_portfolio.isDraft = true;
    crp_portfolio.all_cats = [];

    jQuery(".crp-empty-project-list-alert").show();

    //Perform some actions when window is ready
    jQuery(window).load(function () {
        //Setup sortable lists and grids
        jQuery('.sortable').sortable();
        jQuery('.handles').sortable({
//        handle: 'span'
        });
        jQuery("#crp-gallery-project-list").sortable({items: 'tr'});


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
        jQuery('#crp-project-list').sortable().bind('sortupdate', function(e, ui) {
            //ui.item contains the current dragged element.
            //Triggered when the user stopped sorting and the DOM position has changed.
            crp_updateModel();
        });

        jQuery("#crp-save-portfolio-button").on( 'click', function( evt ){
            evt.preventDefault();

            //Apply last changes to the model
            crp_updateModel();

            //Validate saving
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
                if(picInfoArr && picInfoArr.length > 0) {
                    for (var pi = 0; pi < picInfoArr.length; pi++) {
                        crp_addProject(picInfoArr[pi]);
                    }
                }
            }, true );
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

    function crp_addProject(picInfo){

        //Create new draft project
        var crp_project = {};
        crp_project.id = crp_generateIntId();
        crp_project.title = '';
        crp_project.description = '';
        crp_project.url = '';
        crp_project.isDraft = true;
        crp_project.categories = [];
        crp_project.cover = picInfo;

        crp_portfolio.projects[crp_project.id] = crp_project;
        crp_portfolio.corder.unshift(crp_project.id);

        crp_addProjectItem(crp_project);
        jQuery(".crp-empty-project-list-alert").hide();
        jQuery("#crp-gallery-project-list").scrollTop(0);
    }

    function crp_addProjectItem(crp_project )
    {
        var html = '';

        html +=
            '<tr id="crp-gallery-project-' + crp_project.id + '" data-id="' + crp_project.id + '" class="crp-gallery-project">' +
            '<td class="crp-draggable"><i class="fa fa-reorder"></i></td>' +
            '<td class="crp-attachment">' +
            '<div>' +
            '<div class="crp-attachment-img">' +
            '<div class="crp-attachment-img-overlay" onclick="crp_onProjectEdit(\'' + crp_project.id + '\')"><i class="fa fa-pencil"></i></div>' +
            '</div>' +
            '<input type="hidden" class="crp-project-cover-src" name="project.cover" value="" />' +
            '</div>' +
            '</td>' +
            '<td class="crp-content">' +
            '<div class="crp-content-box"><input type="text" placeholder="Enter title" name="project.title" value=""></div>' +
            '<div class="crp-content-box"><input type="text" placeholder="Enter link (http://domain.com)" name="project.url" value=""></div>' +
            '</td>' +
            '<td class="crp-gallery-delete-proj"><i class="fa fa-trash-o" onclick="onDeleteProject(\'' + crp_project.id + '\')"></i></td>' +
            '</tr>';
        html = jQuery(html);
        jQuery("input[name='project.title']", html).val(crp_project.title);
        jQuery("input[name='project.cover']", html).val(crp_project.cover);
        jQuery("input[name='project.url']", html).val(crp_project.url);
        jQuery("#crp-gallery-project-list").prepend(html);
        crp_changeProjectCover(crp_project.id, crp_project.cover);
    }

    function crp_changeProjectCover(projectId, picInfo) {
        var thumb_img = "<?php echo ($crp_theme == 'dark') ? '/general/glazzed-image-placeholder_dark.png' : '/general/glazzed-image-placeholder.png'; ?>";

        if(picInfo) {
            picInfo.type = crp_attachmentTypePicture;
        }
        var bgImage = (picInfo ? picInfo.src : CRP_IMAGES_URL + thumb_img);

        jQuery("#crp-gallery-project-"+projectId+" .crp-project-cover-src").val(JSON.stringify(picInfo));
        jQuery("#crp-gallery-project-"+projectId+" .crp-attachment-img").css('background', 'url('+bgImage+') center center / cover no-repeat');
    }

    function crp_onProjectEdit(projectId) {
        crp_openMediaUploader(function callback(picInfo) {
            crp_changeProjectCover(projectId, picInfo);
        }, false);
    }

    function crp_updateUI(){

        if(crp_portfolio.title){
            jQuery("#crp-portfolio-title").attr( "value", crp_portfolio.title );
        }

        jQuery("#crp-gallery-project-list").empty();
        if(crp_portfolio.projects && crp_portfolio.corder){
            for(var crp_projectIndex = 0; crp_projectIndex < crp_portfolio.corder.length; crp_projectIndex++){

                var crp_projectId = crp_portfolio.corder[crp_portfolio.corder.length - crp_projectIndex-1];
                if(!crp_portfolio.projects[crp_projectId]){
                    continue;
                }
                var cItem = crp_portfolio.projects[crp_projectId];
                cItem.title = CrpBase64.decode(cItem.title);
                cItem.cover = cItem.cover ? JSON.parse(CrpBase64.decode(cItem.cover)) : null;
                crp_addProjectItem(cItem);

                jQuery(".crp-empty-project-list-alert").hide();
            }
        }

    }

    function crp_updateModel(){
        //To make sure it's valid JS object
        crp_portfolio = validatedPortfolio(crp_portfolio);

        crp_portfolio.title = jQuery("#crp-portfolio-title").attr( "value" );
        crp_portfolio.corder = jQuery("#crp-gallery-project-list").sortable("toArray", {attribute: 'data-id'});
        crp_portfolio.extoptions = {
            all_cats: {},
            type: '<?php echo CRPGridType::CLIENT_LOGOS; ?>'
        };

        jQuery(".crp-gallery-project").each(function(key, elem){
            elem = jQuery(elem);
            crp_selectedProjectId = elem.attr('data-id');
            var crp_activeProject = crp_portfolio.projects[crp_selectedProjectId];

            crp_activeProject.title = CrpBase64.encode(jQuery("input[name='project.title']", elem).val());
            crp_activeProject.cover = CrpBase64.encode(jQuery("input[name='project.cover']", elem).val());
            crp_activeProject.url = jQuery("input[name='project.url']", elem).val();
            crp_activeProject.pics = crp_activeProject.cover;

            crp_portfolio.projects[crp_selectedProjectId] = crp_activeProject;
        });
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

    function onDeleteProject(crp_projectId){
        if(!crp_projectId) return;

        if(!confirm('Are you sure you want to delete?')) {
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

        jQuery("#crp-gallery-project-"+crp_projectId).remove();

    }

    function onPortfolioOptions() {
        if (crp_portfolio.isDraft) {
            alert("Save the draft gallery before changing the view options");
        } else {
            var href = "?page=" + crp_adminPage + "&action=options&id=" + crp_portfolio.id+'&type=<?php echo CRPGridType::CLIENT_LOGOS; ?>';
            crp_loadHref(href);
        }
    }
</script>

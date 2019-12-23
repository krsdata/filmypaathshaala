var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'grid-kit-premium/gkit-shortcode-block', {
    title: 'GridKit',

    icon: 'portfolio',

    category: 'common',

    attributes: {
        content: {
            type: 'string',
            source: 'html',
            selector: 'div'
        },
        gridId: {
            type: 'string',
            source: 'attribute',
            selector: 'div',
            attribute: 'data-gkit-id'
        }
    },

    edit: function( props ) {
        var updateFieldValue = function( val ) {
            props.setAttributes( { content: '[gkit id='+val+']', gridId: val } );
        };
        var options = [];
        for (var i in crp_shortcodes) {
            options.push({label: crp_shortcodes[i].title, value: crp_shortcodes[i].id})
        }
        return el('div', {
            className: props.className
        }, [
            el( 'div', {className: 'gkit-block-box'}, [ el( 'div', {className: 'gkit-block-label'}, 'Select layout' ), el( 'div', {className: 'gkit-block-logo'} )] ),
            el(
                wp.components.SelectControl,
                {
                    label: '',
                    value: props.attributes.gridId,
                    onChange: updateFieldValue,
                    options: options
                }
            )
        ]);
    },
    save: function( props ) {
        return el( 'div', {'data-gkit-id': props.attributes.gridId}, props.attributes.content);
    }
} );

/*! /wp-content/themes/covernews/js/navigation.js */
(function(){var container,button,menu,links,i,len;container=document.getElementById('site-navigation');if(!container){return}
button=container.getElementsByTagName('button')[0];if('undefined'===typeof button){return}
menu=container.getElementsByTagName('ul')[0];if('undefined'===typeof menu){button.style.display='none';return}
menu.setAttribute('aria-expanded','false');if(-1===menu.className.indexOf('nav-menu')){menu.className+=' nav-menu'}
button.onclick=function(){if(-1!==container.className.indexOf('toggled')){container.className=container.className.replace(' toggled','');button.setAttribute('aria-expanded','false');menu.setAttribute('aria-expanded','false')}else{container.className+=' toggled';button.setAttribute('aria-expanded','true');menu.setAttribute('aria-expanded','true')}};links=menu.getElementsByTagName('a');for(i=0,len=links.length;i<len;i++){links[i].addEventListener('focus',toggleFocus,!0);links[i].addEventListener('blur',toggleFocus,!0)}
function toggleFocus(){var self=this;while(-1===self.className.indexOf('nav-menu')){if('li'===self.tagName.toLowerCase()){if(-1!==self.className.indexOf('focus')){self.className=self.className.replace(' focus','')}else{self.className+=' focus'}}
self=self.parentElement}}(function(container){var touchStartFn,i,parentLink=container.querySelectorAll('.menu-item-has-children > a, .page_item_has_children > a');if('ontouchstart' in window){touchStartFn=function(e){var menuItem=this.parentNode,i;if(!menuItem.classList.contains('focus')){e.preventDefault();for(i=0;i<menuItem.parentNode.children.length;++i){if(menuItem===menuItem.parentNode.children[i]){continue}
menuItem.parentNode.children[i].classList.remove('focus')}
menuItem.classList.add('focus')}else{menuItem.classList.remove('focus')}};for(i=0;i<parentLink.length;++i){parentLink[i].addEventListener('touchstart',touchStartFn,!1)}}}(container))})()
;
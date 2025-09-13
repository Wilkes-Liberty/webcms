/**
 * @file
 * WL Language Switcher JavaScript for Gin toolbar integration.
 */

(function (Drupal) {
  'use strict';

  /**
   * Inject the language switcher into the gin secondary toolbar.
   */
  Drupal.behaviors.wlLanguageSwitcher = {
    attach: function (context, settings) {
      console.log('WL Language Switcher: Behavior attached', settings);
      console.log('WL Language Switcher: Context:', context);
      
      // Check if language switcher already exists anywhere on the page
      if (document.querySelector('.wl-flag-language-switcher')) {
        console.log('WL Language Switcher: Already exists on page, skipping');
        return;
      }
      
      console.log('WL Language Switcher: Creating language switcher');
      
      // Always create the switcher for now, ignore settings check temporarily
      if (true) {
          
          console.log('WL Language Switcher: Looking for suitable container');
          
          // Try multiple possible containers in order of preference
          var containers = [
            '.gin-secondary-toolbar__layout-container',
            '.gin-secondary-toolbar',
            '#toolbar-administration-secondary',
            '.toolbar-bar',
            '#toolbar-bar',
            'body'
          ];
          
          var container = null;
          var containerType = '';
          
          for (var i = 0; i < containers.length; i++) {
            container = document.querySelector(containers[i]);
            if (container) {
              containerType = containers[i];
              break;
            }
          }
          
          if (container) {
            console.log('WL Language Switcher: Found container:', containerType);
            
            // Create the language switcher HTML
            var languageSwitcher = document.createElement('div');
            languageSwitcher.className = 'wl-flag-language-switcher';
            
            // Adjust positioning based on container type
            var positioning = '';
            if (containerType === 'body') {
              positioning = 'position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 8px; background: white; padding: 8px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);';
            } else if (containerType === '.gin-secondary-toolbar__layout-container' || containerType === '.gin-secondary-toolbar') {
              // For Gin secondary toolbar, position to avoid devel menu
              positioning = 'position: absolute; right: 8em; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; gap: 8px;';
            } else {
              // For other toolbars, use default positioning
              positioning = 'position: absolute; right: 16px; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; gap: 8px;';
            }
            
            languageSwitcher.style.cssText = positioning;
            
            // Add language links
            var languages = [
              {code: 'en', class: 'lang-en', title: 'Switch to English', name: 'English'},
              {code: 'es', class: 'lang-es', title: 'Cambiar a Español', name: 'Español'},
              {code: 'ru', class: 'lang-ru', title: 'Переключить на русский', name: 'Русский'}
            ];
            
            // Get current language from HTML lang attribute or default to 'en'
            var currentLang = document.documentElement.lang || 'en';
            console.log('WL Language Switcher: Current language is', currentLang);
            
            languages.forEach(function (lang) {
              var link = document.createElement('a');
              link.className = 'language-link ' + lang.class;
              link.href = '/' + lang.code;
              link.title = lang.title;
              link.setAttribute('hreflang', lang.code);
              link.setAttribute('aria-label', lang.title);
              link.style.cssText = 'width: 28px; height: 20px; border-radius: 3px; border: 1px solid #d4d4d8; display: block; text-decoration: none;';
              
              if (lang.code === currentLang) {
                link.classList.add('active');
                link.style.borderColor = '#3b82f6';
              }
              
              // Add visually hidden text for accessibility
              var span = document.createElement('span');
              span.className = 'text visually-hidden';
              span.textContent = lang.name;
              link.appendChild(span);
              
              languageSwitcher.appendChild(link);
            });
            
            // Make sure container is relatively positioned
            if (getComputedStyle(container).position === 'static') {
              container.style.position = 'relative';
            }
            
            // Append to the container
            container.appendChild(languageSwitcher);
            console.log('WL Language Switcher: Added to gin secondary toolbar');
          } else {
            console.log('WL Language Switcher: No suitable container found. Tried:', containers);
          }
      } else {
        console.log('WL Language Switcher: Settings check failed (temporarily disabled)');
      }
    }
  };

})(Drupal);

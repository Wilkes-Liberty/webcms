/**
 * @file
 * WL Language Switcher JavaScript for Gin toolbar integration.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Inject the language switcher into the gin secondary toolbar.
   */
  Drupal.behaviors.wlLanguageSwitcher = {
    attach: function (context, settings) {
      console.log('WL Language Switcher: Behavior attached', settings);
      console.log('WL Language Switcher: Context:', context);

      // Use once to prevent multiple attachments
      once('wl-language-switcher', 'body', context).forEach(function (element) {

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
              positioning = 'position: absolute; right: 15em; top: 50%; transform: translateY(-50%); z-index: 999; display: flex; gap: 8px;';
            } else {
              // For other toolbars, use default positioning
              positioning = 'position: absolute; right: 16px; top: 50%; transform: translateY(-50%); z-index: 10; display: flex; gap: 8px;';
            }

            languageSwitcher.style.cssText = positioning;

            // Add language links
            var languages = [
              {code: 'en', class: 'lang-en', title: 'Switch to English', name: 'EN'},
              {code: 'es', class: 'lang-es', title: 'Cambiar a Español', name: 'ES'},
              {code: 'ru', class: 'lang-ru', title: 'Переключить на русский', name: 'RU'}
            ];

            // Get current language from HTML lang attribute or default to 'en'
            var currentLang = document.documentElement.lang || 'en';
            console.log('WL Language Switcher: Current language is', currentLang);

            languages.forEach(function (lang, index) {
              // Add separator before each link (except the first)
              if (index > 0) {
                var separator = document.createElement('span');
                separator.className = 'language-separator';
                separator.textContent = '/';
                separator.style.cssText = 'opacity: .3; font-size: var(--gin-font-size-xs); vertical-align: middle; display: inline-block; padding: 0 .5em;';
                languageSwitcher.appendChild(separator);
              }

              var link = document.createElement('a');
              link.className = 'language-link ' + lang.class;
              link.href = '/' + lang.code;
              link.title = lang.title;
              link.setAttribute('hreflang', lang.code);
              link.setAttribute('aria-label', lang.title);
              link.style.cssText = 'text-decoration: none; font-size: var(--gin-font-size-xs); font-weight: var(--gin-font-weight-normal); color: var(--gin-color-text-light); line-height: 2; border-radius: var(--gin-border-xxs); padding: 2px; vertical-align: middle;';
              link.textContent = lang.name; // Display language code as text

              if (lang.code === currentLang) {
                link.classList.add('active');
                link.style.cssText += ' color: var(--gin-color-primary); font-weight: var(--gin-font-weight-semibold);';
              }

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
      }); // End once.forEach
    }
  };

})(Drupal, once);

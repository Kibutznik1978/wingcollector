=== Toolset Framework Installer ===
Contributors: codex-m, brucepearson, jozik, AmirHelzer
Donate link: http://wp-types.com/documentation/views-demos-downloader/
Tags: CMS, Views, Demos, Download
License: GPLv2
Requires at least: 3.8.1
Tested up to: 4.5.3
Stable tag: 2.0.2

Download complete reference sites for Types and Views.

== Description ==

This plugin lets you download complete, working reference designs for Types and Views. You'll be able to experiment with everything locally and follow our online tutorials.

If you also have Views plugins, install it and you'll see the source for all the Views and View Templates in the demo site.

Documentation:
http://wp-types.com/documentation/views-demos-downloader/

= Requirements =

* A fresh WordPress site
* Write access to the theme and uploads directories
* Some additional plugins (each demo will tell you which other plugins it needs and where to get them)


== Installation ==

1. Upload 'types' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Can I use this on a live site? =

You can, but it highly not recommended. You should use this on local test sites.

== Changelog ==

= 0.1.2 =
* Added this readme file

= 1.1.3 =
Sync with Views 1.1.3

= 1.1.3.1 =
Fix for embedded Types

= 1.2 =
Correcting some bugs and adding code for inline documentation plugin, compatibility with new reference sites and with Types 1.2 and Views 1.2

= 1.2.1 =
Corrected some issues on Framework Installer plugin, increasing compatibility with version 1.2.1 of Views and Types

= 1.2.2 =
Added features on importing modules and Bootmag site. Increasing compatibility with Views 1.2.2. and Types 1.3. Corrected some bugs during import.

= 1.2.3 =
Added features on importing Classifieds site. Increasing compatibility with Views 1.2.3. and Types 1.3.1. Corrected some bugs during import.

= 1.2.4 =
Added some new features on importing Classifieds site. Increasing compatibility with Views and Types. Corrected some bugs during import.

= 1.3 =
Sync with Views 1.3
Improve downloading stability for slower connections when downloading demo sites in standalone localhost.
Improve method of downloading images for Framework Installer specially for slower connections.
Added support for new Toolset Bootstrap reference sites.
Added new Framework Installer site reset feature to easily reset database if users wants to download another demo site.

= 1.3.0.2 =
Sync with Views 1.3.0.2

= 1.3.1 =
Sync with Views 1.3.1

= 1.4 =
Sync with Views 1.4 and adding support for Bootstrap Toolset Classifieds and BootCommerce Multilingual.

= 1.5 =
Sync with Views 1.5

= 1.5.1 =
Fix compatibility issues with Types plugin on image custom field handling.

= 1.5.2 =
Fix compatibility issues with Multilingual Classifieds Site.

= 1.5.3 =
Added support for Multilingual Classifieds site Ad package feature.

= 1.5.4 =
Sync with Views 1.5.1 and Types 1.5.4
Added feature to dismiss Framework Installer notice after import.
Fixed PHP notices occurring after WordPress reset.

= 1.5.5 =
Added feature to allow downloading of multilingual reference sites without WPML plugins.
Fixed incompatibility issue of Framework Installer with WooCommerce versions 2.1+.
Deleted _callback_views-commerce.php since reference sites does not anymore include Views Base Commerce.
Added capability of Framework Installer to suggest the tested and compatible plugin versions of the site being imported.

= 1.5.6 =
Compatibility with WooCommerce 2.1.4.
Allow to import a newly generated WooCommerce export file for BootCommerce site.
Fixes a couple of importing issues relating to Classifieds and BootCommerce site relating to WooCommerce 2.1.4 update.

= 1.5.7 =
Compatibility with WooCommerce 2.1.5.
Added hook to fix the fatal error when importing multilingual websites with WPML 3.1+.
Changed plugin name to Framework Installer.
Fix some importing issues in Classifieds site using the latest Toolset plugins.

= 1.5.9 =
 Fixed some importing bugs on WPML string translations for sites with multilingual implementation. 
 Compatibility with the latest version of Classifieds Multilingual reference site.
 Updated embedded Types to use the latest version 1.5.5 of Types.
 Added WooCommerce shop page ID to WooCommerce settings import.
 Conditionally output errors when debugging is set to true.
 Removed some deprecated import code belonging to old/unsupported reference sites.
 Revised module manager import procedure to put it after theme import so modules can be imported using embedded module manager mode in Toolset Bootstrap theme.
 Revised module manager import function to use new automatic import function for modules in module manager version 1.3.
 Allowed Types and Views plugin full version to be activated automatically after import for sites with modules import.
 Added methods to flush permalinks for sites imported with WooCommerce plugin enabled.
 Added controls for Framework installer to check if wp-content is writable. As a requirement for automatic modules import from reference sites.
 Compatibility with the latest Types and Views release.
 
 ==1.6.0==
 Completed the adding import support of Bootstrap Real Estate. 
 Compatibility with Views 1.6.1 plugin version.
 Compatibility with Types 1.5.7 plugin version.
 Added new feature to highlight WPML plugins as optional plugins for importing sites with multilingual support.
 
 ==1.6.1==
 Removed warning about override content for default content. Show override content only when non-default content is detected.
 Compatibility with CRED 1.3 release. Latest Types and Views.
 Improved version number difference warning messages by informing user whether to upgrade or downgrade installed plugins.
 Removed wpv_refresh_cred_form_after_import method because this should be handled automatically by CRED 1.3.
 
 ==1.6.2==
Compatibility with Types 1.6.1 and Views 1.6.3 release.

 ==1.6.3==
Compatibility with Types 1.6.2 release.

 ==1.6.4==
Compatibility with Types 1.6.3 and Views 1.6.4 release.

 ==1.6.5==
Compatibility with Types 1.6.4 and CRED 1.3.4 release.
Fixed conflict on sitename with WPML string translation.

 ==1.6.6==
Compatibility with WooCommerce Views 2.4 release.
Fixed conflict on sitename with WPML string translation.
Added CSS fixes on Manage Sites admin screen.

 ==1.6.7==
Added support for importing Layouts
Added support for importing the new Classifieds site with Layouts
Compatibility with Types 1.6.5 and Views 1.7

 ==1.6.8==
Added support for downloading media for Toolset Bootstrap starter site

==1.6.9==
Compatibility with WordPress 4.2.1 and latest Toolset releases (Views 1.8 + , Layouts 1.1)

 ==1.7==
Remove Views, Types, CRED embedded calls and replace with Installer plugin.
New admin screen.
Added error handling on failed or problematic imports.
Allows client to download non-WPML version of the site even though WPML plugins are installed.
Added controls on activation of optional WPML plugins when importing sites.
Fixed wrong password being sent when using reset feature.
Added support for BootCommerce site with Layouts.
Added support in Ref site theme default site for Views.
Improved Layouts import calls.

 ==1.7.1==
 Fixed a bug when Framework installer flags an empty site after importing a blank site.
 Fixed issue on My Company layouts sites no image URL is still using ref site URL after import.
 Reverse order of ref sites in manage sites screen with the latest sites first.
 Fixed usability issue on Framework installer manage sites screen when user does not install required plugins.
 Fixed multilingual issue on detecting empty site when using non-English WordPress version.
 Added basic RTL support for the new admin screen.
 Added controls for display error messages when importing multilingual version with incomplete set of plugins required.
 Updated Installer screen button texts.
 Fixed usability issue when the user is not connected to the Internet.
 Removed tutorial button links if the site does not have tutorials.
 Added reset message only when relevant.
 Improved required plugins error messages by telling users where to obtain a copy of that plugin.
 
 ==1.7.2==
 Compatibility with WPML 3.2
 Fixed bug on registering woocommerce product taxonomy on non-ecommerce sites.
 Refactored import and post import functions in the code for easy maintenance/clarity
 Fixed Types - Where to display group - Templates not imported.
 Reloaded user to manage sites screen after running install and activate on any admin pages.
 
 ==1.7.3==
 Loaded latest version of Installer.
 
 ==1.8==
 Improved responsiveness in mobile devices.
 Improved detection of problematic Installer settings due to connection issues.
 Added support for merging sites presentation in Manage sites as one (Layouts and non-Layouts version)
 Refactored import methods and added API support for extending new reference sites.
 Added support for new WPML 3.2.2+ methods.
 Fixed issues on reset string nonces.
 Added support for new multilingual My Company sites.
 Added support for new Views tutorial sites.
 Added import support for WordPress discussion settings.
 
 ==1.8.1==
 Added compatibility to WordPress 4.2.3
 Updated Installer code base to version 1.6.4 for faster response.
 
 ==1.8.2==
 Fixed a couple of importing issues and notices.
 Fixed fatal error when the ref sites does not have images set on the exporter end. 
 Fixed importing issues with Layouts 1.3.
 Fixed importing issues with WPML 3.2.2+
 Added support for non-multilingual import of a multilingual site in Discover-WP.com
 Added support for new BootCommerce with Views and with Layouts multilingual reference sites.
 Added an option in Discover site registration to select whether Views or Layouts type of reference site.
 Customized welcome panel at the dashboard after import.
 Allow user to deactivate Framework installer at Dashboard via the welcome panel.
 Removed the procedure text message from the initial page of select a reference site.
 Removed link from the by 'OnTheGoSystems' in the reference site description.
 Removed unneeded importing steps depending on the type of reference site being installed.
 Improved text and links for the required plugins. 
 Improved speed and responsiveness in Manage sites screen and overall backend when using Framework installer
 Improved detection of a non-blank site.
 
 ==1.8.3==
 Added support for the new WooCommerce tutorial training sites.
 
 ==1.8.4==
 Added support for search and replacement of hostnames inside layouts translated strings. 
 Removed unnecessary dependencies to WPML multilingual plugin.
 Added support for new Toolset Classifieds sites.
 Fixed issues on Layouts translation status after import.
 Added support for importing translation jobs created from reference sites.
 Added Google analytics arguments to links going out to wp-types.com.
 Added support for customized welcome dashboard with links and videos to tutorials.
 
 ==1.8.5==
 Fixed dashboard-override-new.js warnings in console when importing in discover-wp.
 Removed duplicated DB Entries in Classifieds with Layouts ML demo site import.
 Fixed Layouts set as not translated in an import.
 
 ==1.8.6==
 Compatibility with WPML 3.3.
 Compatibility with Views 1.11.
 Updated Installer code base to 1.6.8
 
  ==1.8.7==
 Fixed issues with un-replaced URLs or media after import.
 Fixed WordPress database error Blog Title in Classifieds during reset operations.
 Fixed Premium Ads slug is not imported correctly for Classifieds with Layouts.
 Updated Installer code base to 1.7.2.
 Updated Embedded Plugins to version 1.4.
 Updated method on exporting nav menu options.
 Added support for updated Views tutorial sites.
 
 ==1.8.8==
 Added support for new Bootstrap magazine site.
 Added support for new Real estate multilingual site.
 Added support for importing reference site exported versions.
 Compatibility with WordPress 4.4 and WPML 3.3.2+
 
  ==1.8.9==
 Enhanced the support of the new Real estate site.
 Compatibility with WPML 3.3.4+
 Updated Installer to version 1.7.3
 Fixed issue with WPML language switcher settings for nav menu not properly imported.
 Fixed issue on widget body text not translated after multilingual import.
 Fixed issue on CRED context not being updated on import for those that are hard-coded inside CRED body.
 Added support for refreshing ref site versions when export XML is updated.
 Added support for CRED and WPML integration for new Real estate site.  
 
  ==1.9.0==
 Added support for locally targeted images inside tutorial dashboard in Framework intaller.
 Fixed new PHP notices found during site reset of a multilingual import.
 Fixed duplicated entries when importing a ML version of the demo site.
 
  ==1.9.1==
 Added support for Bootstrap Real estate layouts version.
 Added support for auto-adjusting custom menus used inside Layouts cell after import.
 Fixed import issue on CRED context when using ampersands.
 Removed auto-string registration filters provided by WPML string translation.
 Added support for the new Magazine Views version.
 Fixed issue on missing required plugins for merged plugin presentation of non-multilingual site.
 
  ==1.9.2==
 Compatibility with WPML 3.3.6+
 
  ==1.9.3==
 Updated Installer code base to version 1.7.5
 Disable runtime translation when doing import to prevent issues.
 Compatibility to new Toolset plugin names and new versions.
 Added compatibility to Bedrock Boilerplate WP framework.
 Fixed some strings not being translated after import.
 
 ==1.9.4==
 Support for first release of new Bootstrap estate sites (Layouts and Views version non-multilingual)
 
 ==1.9.5==
 Added support for logging reference sites installation count to the Toolset server.
 
 ==1.9.6==
 Added release notes link.
 Fixed fatal error wpv_admin_import_export_simplexml2array() when importing Layouts reference sites with Embedded Views.
 Added support for multilingual versions of the new Real Estate sites.
 
  ==1.9.7==
 Compatibility with WordPress 4.5 and WPML 3.3.7+
 
  ==1.9.8==
 Support for Toolset unified menu implementation.
 
  ==1.9.9==
 Fixed: Front-end theme issue of the import of Real Estate with Views ML in discover-wp dev site when debug is enabled.
 Compatibility with WPML 3.3.8 +
 
  ==2.0==
 Updated Installer code base to version 1.7.8.
 Compatibility with WPML 3.4+ and Toolset 2.1+
 
  ==2.0.1==
 Fixed PHP notices when refsite tutorial description is not set.
 Support for first release of CRED Tutorial reference sites.
 Added support for automated import of CRED user forms.
 
  ==2.0.2==
 Added support for appending Google Analytics arguments on image links for dashboard tutorials.
 Fixed some outdated text on the plugin settings.
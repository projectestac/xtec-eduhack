== Copyright ==

XTEC Eduhack is distributed under the terms of the GNU GPL

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

http://www.gnu.org/licenses/gpl-2.0.html

== Usage ==

This plugin works only with WordPress multisite installs. Unfortunatelly, adding
rewrite rules dinamically does not work well with multisite installs and, thus,
the following rule must be added manually to your .httacces file for the plugin
to work as expected:

  RewriteRule ^(eduhack/)(.*) wp-content/plugins/xtec-eduhack/$2 [L]

In order to configure the template that must be cloned automatically, please
see the availabe options on the xtec-eduhack/xtec-eduhack.php file.

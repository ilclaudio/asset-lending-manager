-- Retrieve all ALMGR plugin settings.
--
-- All settings are stored as a single serialized PHP array in wp_options
-- under the key 'almgr_settings'. If the row is missing, no settings have
-- been saved yet and the plugin uses the defaults defined in
-- ALMGR_Settings_Manager::get_defaults().
--
-- The option_value column contains a PHP serialize() string, for example:
--   a:3:{s:5:"email";a:3:{s:9:"from_name";s:4:"AAGG";...}...}
-- WordPress automatically unserializes it when reading via get_option().

SELECT
	option_name,
	option_value,
	autoload
FROM wp_options
WHERE option_name = 'almgr_settings';

-- Show ID, name, and ALM ACF fields for one asset.
-- Replace 49 with the target asset ID.
SELECT
	p.ID                           AS asset_id,
	p.post_title                   AS asset_name,
	pm_manufacturer.meta_value     AS manufacturer,
	pm_model.meta_value            AS model,
	pm_data_acquisto.meta_value    AS data_acquisto,
	pm_cost.meta_value             AS cost,
	pm_dimensions.meta_value       AS dimensions,
	pm_weight.meta_value           AS weight,
	pm_location.meta_value         AS location,
	pm_components.meta_value       AS components,
	pm_user_manual.meta_value      AS user_manual,
	pm_technical_data.meta_value   AS technical_data_sheet,
	pm_serial_number.meta_value    AS serial_number,
	pm_external_code.meta_value    AS external_code,
	pm_notes.meta_value            AS notes
FROM wp_posts p
LEFT JOIN wp_postmeta pm_manufacturer
	ON pm_manufacturer.post_id = p.ID
	AND pm_manufacturer.meta_key = 'manufacturer'
LEFT JOIN wp_postmeta pm_model
	ON pm_model.post_id = p.ID
	AND pm_model.meta_key = 'model'
LEFT JOIN wp_postmeta pm_data_acquisto
	ON pm_data_acquisto.post_id = p.ID
	AND pm_data_acquisto.meta_key = 'data_acquisto'
LEFT JOIN wp_postmeta pm_cost
	ON pm_cost.post_id = p.ID
	AND pm_cost.meta_key = 'cost'
LEFT JOIN wp_postmeta pm_dimensions
	ON pm_dimensions.post_id = p.ID
	AND pm_dimensions.meta_key = 'dimensions'
LEFT JOIN wp_postmeta pm_weight
	ON pm_weight.post_id = p.ID
	AND pm_weight.meta_key = 'weight'
LEFT JOIN wp_postmeta pm_location
	ON pm_location.post_id = p.ID
	AND pm_location.meta_key = 'location'
LEFT JOIN wp_postmeta pm_components
	ON pm_components.post_id = p.ID
	AND pm_components.meta_key = 'components'
LEFT JOIN wp_postmeta pm_user_manual
	ON pm_user_manual.post_id = p.ID
	AND pm_user_manual.meta_key = 'user_manual'
LEFT JOIN wp_postmeta pm_technical_data
	ON pm_technical_data.post_id = p.ID
	AND pm_technical_data.meta_key = 'technical_data_sheet'
LEFT JOIN wp_postmeta pm_serial_number
	ON pm_serial_number.post_id = p.ID
	AND pm_serial_number.meta_key = 'serial_number'
LEFT JOIN wp_postmeta pm_external_code
	ON pm_external_code.post_id = p.ID
	AND pm_external_code.meta_key = 'external_code'
LEFT JOIN wp_postmeta pm_notes
	ON pm_notes.post_id = p.ID
	AND pm_notes.meta_key = 'notes'
WHERE p.post_type = 'alm_asset'
AND p.ID = 49
LIMIT 1;

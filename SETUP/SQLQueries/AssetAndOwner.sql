-- Show the asset name and the current owner.
SELECT
	p.ID              AS asset_id,
	p.post_title      AS asset_name,
	pm.meta_value     AS owner_id,
	u.display_name    AS owner_name
FROM wp_posts p
LEFT JOIN wp_postmeta pm
	ON pm.post_id   = p.ID
	AND pm.meta_key = '_almgr_current_owner'
LEFT JOIN wp_users u
	ON u.ID = pm.meta_value
WHERE p.post_type = 'almgr_asset'
AND p.ID IN (49,45,52)
ORDER BY p.ID;

	{include file="inc/form_properties.tpl" fixed=false}
	
	{assign_associative var="params" object=$object|default:null}
	{$view->element('form_categories')}
	
	{assign_associative var="params" object=$object|default:null}
	{$view->element('form_tags', $params)}

	{$view->element('form_geotag')}
	
	{$view->element('form_assoc_objects',['object_type_id' => {$conf->objectTypes.section.id}, 'availabeRelations' => ['attach' => 'multimedia items']])}

	{assign_associative var="params" object=$object|default:null}
	{$view->element('form_translations', $params)}

	{assign_associative var="params" object=$object|default:null}	
	{$view->element('form_advanced_properties')}
	
	{$view->element('form_custom_properties')}
	
	{assign_associative var="params" el=$object|default:null recursion=true}
	{$view->element('form_permissions', $params)}

	{$view->element('form_versions')}

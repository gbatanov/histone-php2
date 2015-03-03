

{{macro button(settings)}}

	{{var color = settings.color}}
	{{var view = settings.view}}
	{{var type = settings.type}}
	{{var data = settings.data}}
	{{var href = settings.href}}
	{{var target = settings.target}}
	{{var value = settings.value.strip()}}
	{{var class = settings.class}}
	{{var classWidth = settings.classWidth}}
	{{var disabled = settings.disabled}}
	{{var autoDisable = settings.autoDisable}}
	{{var tagName = type = 'link' ? 'a' : 'div'}}

	{{var icon = settings.icon}}
	{{var iconRight = settings.iconRight}}

	<{{tagName}} class="{{[
		'ui-button',
		view = 'big' ? 'ui-button-big',
		color = 'white' ? 'ui-button-white',
		color = 'gray' ? 'ui-button-gray',
		color = 'purple' ? 'ui-button-purple',
		color = 'pale' ? 'ui-button-pale',
		type = 'submit' ? 'ui-button-submit',
		type = 'reset' ? 'ui-button-reset',
		disabled ? 'ui-button-disabled',
		icon ? 'ui-button-icon',
		iconRight ? 'ui-button-icon-right',
		value = '' ? 'ui-button-text-no',
		class
	]}}" {{if href}}href="{{href}}"{{/if}}
		{{if target}}target="{{target}}"{{/if}}
		{{if autoDisable}}data-autodisable="true"{{/if}}
		{{if data}}data-data='{{data.toJSON()}}'{{/if}}><span class="{{[
			'ui-button-but',
			classWidth
		]}}">
		{{if icon}}
			{{if !iconRight}}
				<i class="ui-button-icon-{{icon}}"></i>
			{{/if}}
			<span class="ui-button-text">{{value}}</span>
			{{if iconRight}}
				<i class="ui-button-icon-{{icon}}"></i>
			{{/if}}
		{{else}}
			{{value}}
		{{/if}}
		</span>
		{{if type = 'submit' || type = 'reset'}}
			<input type="{{type}}" value=""
				{{if disabled}}disabled="disabled"{{/if}}
			/>
		{{/if}}
	</{{tagName}}>

{{/macro}}

{{return button}}
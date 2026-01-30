/**
 * FlavorFlow Admin – React SPA entry point.
 *
 * Uses @wordpress/element (React wrapper) and @wordpress/components.
 * Bundled by @wordpress/scripts or loaded as a pre-built bundle.
 *
 * @package FlavorFlow
 */

( function () {
	'use strict';

	const { createElement: el, useState, useEffect, Fragment } = wp.element;
	const { TabPanel, Button, TextControl, ToggleControl, SelectControl,
	        Card, CardBody, CardHeader, Spinner, Notice, Icon, Modal,
	        TextareaControl, Flex, FlexItem, FlexBlock } = wp.components;
	const apiFetch = wp.apiFetch;
	const { __ } = wp.i18n;

	/* ─── Helpers ─────────────────────────────────────────────────────── */

	const rest = ( path, opts = {} ) =>
		apiFetch( { path: '/flavor-flow/v1' + path, ...opts } );

	/* ─── Router (hash-based) ────────────────────────────────────────── */

	function useHashRoute() {
		const [ route, setRoute ] = useState( window.location.hash || '#/' );
		useEffect( () => {
			const handler = () => setRoute( window.location.hash || '#/' );
			window.addEventListener( 'hashchange', handler );
			return () => window.removeEventListener( 'hashchange', handler );
		}, [] );
		return route;
	}

	/* ─── App Shell ──────────────────────────────────────────────────── */

	function App() {
		const page = window.flavorFlowAdmin?.page || 'flavor-flow';
		const route = useHashRoute();

		const pageMap = {
			'flavor-flow':          () => WorkflowsPage( { route } ),
			'flavor-flow-logs':     LogsPage,
			'flavor-flow-settings': SettingsPage,
			'flavor-flow-license':  LicensePage,
		};

		const PageComponent = pageMap[ page ] || pageMap[ 'flavor-flow' ];

		return el( 'div', { className: 'flavor-flow-app' },
			el( 'div', { className: 'ff-header' },
				el( 'h1', null, el( Icon, { icon: 'randomize' } ), ' FlavorFlow' ),
				el( 'span', { className: 'ff-version' }, 'v' + ( window.flavorFlowAdmin?.version || '1.0.0' ) ),
			),
			el( PageComponent )
		);
	}

	/* ─── Workflows Page ─────────────────────────────────────────────── */

	function WorkflowsPage( { route } ) {
		// Simple sub-routing: #/edit/123
		const editMatch = route.match( /^#\/edit\/(\d+)$/ );

		if ( editMatch ) {
			return el( WorkflowEditor, { id: parseInt( editMatch[ 1 ], 10 ) } );
		}

		if ( route === '#/new' ) {
			return el( WorkflowEditor, { id: 0 } );
		}

		return el( WorkflowList );
	}

	/* ─── Workflow List ──────────────────────────────────────────────── */

	function WorkflowList() {
		const [ workflows, setWorkflows ] = useState( [] );
		const [ loading, setLoading ] = useState( true );

		const load = () => {
			setLoading( true );
			rest( '/workflows?per_page=50' )
				.then( setWorkflows )
				.catch( () => {} )
				.finally( () => setLoading( false ) );
		};

		useEffect( load, [] );

		const toggle = ( id ) => {
			rest( `/workflows/${ id }/toggle`, { method: 'PUT' } ).then( load );
		};

		const del = ( id ) => {
			if ( ! confirm( __( 'Delete this workflow?', 'flavor-flow' ) ) ) return;
			rest( `/workflows/${ id }`, { method: 'DELETE' } ).then( load );
		};

		const duplicate = ( id ) => {
			rest( `/workflows/${ id }/duplicate`, { method: 'POST' } ).then( load );
		};

		if ( loading ) {
			return el( 'div', { className: 'ff-loading' }, el( Spinner ) );
		}

		return el( Fragment, null,
			el( Flex, { className: 'ff-toolbar' },
				el( FlexBlock ),
				el( FlexItem, null,
					el( Button, {
						variant: 'primary',
						onClick: () => { window.location.hash = '#/new'; },
					}, __( '+ New Workflow', 'flavor-flow' ) ),
				),
			),
			workflows.length === 0
				? el( Card, null, el( CardBody, null, el( 'p', null, __( 'No workflows yet. Create your first one!', 'flavor-flow' ) ) ) )
				: el( 'table', { className: 'widefat ff-table' },
					el( 'thead', null,
						el( 'tr', null,
							el( 'th', null, __( 'Name', 'flavor-flow' ) ),
							el( 'th', null, __( 'Trigger', 'flavor-flow' ) ),
							el( 'th', null, __( 'Status', 'flavor-flow' ) ),
							el( 'th', null, __( 'Actions', 'flavor-flow' ) ),
						),
					),
					el( 'tbody', null,
						workflows.map( ( wf ) =>
							el( 'tr', { key: wf.id },
								el( 'td', null,
									el( 'a', {
										href: `#/edit/${ wf.id }`,
										className: 'ff-wf-link',
									}, wf.title || __( '(Untitled)', 'flavor-flow' ) ),
								),
								el( 'td', null, wf.trigger || '—' ),
								el( 'td', null,
									el( 'span', {
										className: 'ff-badge ' + ( wf.enabled ? 'ff-badge--active' : 'ff-badge--inactive' ),
									}, wf.enabled ? __( 'Active', 'flavor-flow' ) : __( 'Inactive', 'flavor-flow' ) ),
								),
								el( 'td', { className: 'ff-actions-cell' },
									el( Button, { isSmall: true, variant: 'secondary', onClick: () => toggle( wf.id ) },
										wf.enabled ? __( 'Disable', 'flavor-flow' ) : __( 'Enable', 'flavor-flow' ) ),
									el( Button, { isSmall: true, variant: 'secondary', onClick: () => duplicate( wf.id ) },
										__( 'Duplicate', 'flavor-flow' ) ),
									el( Button, { isSmall: true, isDestructive: true, onClick: () => del( wf.id ) },
										__( 'Delete', 'flavor-flow' ) ),
								),
							)
						),
					),
				),
		);
	}

	/* ─── Workflow Editor ────────────────────────────────────────────── */

	function WorkflowEditor( { id } ) {
		const [ system, setSystem ] = useState( null );
		const [ form, setForm ] = useState( {
			title: '',
			trigger: '',
			conditions: { logic: 'AND', rules: [] },
			actions: [],
			enabled: false,
		} );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( null );

		useEffect( () => {
			rest( '/system' ).then( setSystem );
			if ( id > 0 ) {
				rest( `/workflows/${ id }` ).then( ( data ) => {
					setForm( {
						title: data.title || '',
						trigger: data.trigger || '',
						conditions: ( typeof data.conditions === 'object' && data.conditions && data.conditions.logic )
							? data.conditions
							: { logic: 'AND', rules: [] },
						actions: Array.isArray( data.actions ) ? data.actions : [],
						enabled: !! data.enabled,
					} );
				} );
			}
		}, [ id ] );

		const save = () => {
			setSaving( true );
			const method = id > 0 ? 'PUT' : 'POST';
			const path   = id > 0 ? `/workflows/${ id }` : '/workflows';
			rest( path, { method, data: form } )
				.then( ( data ) => {
					setNotice( { status: 'success', message: __( 'Workflow saved.', 'flavor-flow' ) } );
					if ( ! id ) {
						window.location.hash = `#/edit/${ data.id }`;
					}
				} )
				.catch( ( err ) => {
					setNotice( { status: 'error', message: err.message || __( 'Save failed.', 'flavor-flow' ) } );
				} )
				.finally( () => setSaving( false ) );
		};

		if ( ! system ) {
			return el( 'div', { className: 'ff-loading' }, el( Spinner ) );
		}

		const triggerOptions = [ { label: __( '— Select Trigger —', 'flavor-flow' ), value: '' } ]
			.concat( system.triggers.map( ( t ) => ( { label: `${ t.group }: ${ t.label }`, value: t.slug } ) ) );

		const actionOptions = system.actions.map( ( a ) => ( { label: `${ a.group }: ${ a.label }`, value: a.slug } ) );

		const update = ( key, value ) => setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );

		const addAction = () => {
			const newActions = [ ...form.actions, { type: actionOptions[ 0 ]?.value || '', config: {} } ];
			update( 'actions', newActions );
		};

		const removeAction = ( index ) => {
			update( 'actions', form.actions.filter( ( _, i ) => i !== index ) );
		};

		const updateAction = ( index, key, value ) => {
			const newActions = [ ...form.actions ];
			if ( key === 'type' ) {
				newActions[ index ] = { ...newActions[ index ], type: value };
			} else {
				newActions[ index ] = { ...newActions[ index ], config: { ...newActions[ index ].config, [ key ]: value } };
			}
			update( 'actions', newActions );
		};

		// Condition builder.
		const addCondition = () => {
			const newRules = [ ...( form.conditions.rules || [] ), { field: '', type: 'string', operator: 'equals', value: '' } ];
			update( 'conditions', { ...form.conditions, rules: newRules } );
		};

		const removeCondition = ( index ) => {
			const newRules = form.conditions.rules.filter( ( _, i ) => i !== index );
			update( 'conditions', { ...form.conditions, rules: newRules } );
		};

		const updateCondition = ( index, key, value ) => {
			const newRules = [ ...form.conditions.rules ];
			newRules[ index ] = { ...newRules[ index ], [ key ]: value };
			update( 'conditions', { ...form.conditions, rules: newRules } );
		};

		// Get payload fields for the selected trigger.
		const selectedTrigger = system.triggers.find( ( t ) => t.slug === form.trigger );
		const payloadFields = selectedTrigger?.payload_schema?.properties
			? Object.keys( selectedTrigger.payload_schema.properties )
			: [];

		return el( 'div', { className: 'ff-editor' },
			notice && el( Notice, {
				status: notice.status,
				isDismissible: true,
				onRemove: () => setNotice( null ),
			}, notice.message ),

			el( Flex, { className: 'ff-toolbar' },
				el( FlexItem, null,
					el( Button, { variant: 'secondary', onClick: () => { window.location.hash = '#/'; } },
						__( 'Back to Workflows', 'flavor-flow' ) ),
				),
				el( FlexBlock ),
				el( FlexItem, null,
					el( ToggleControl, {
						label: __( 'Enabled', 'flavor-flow' ),
						checked: form.enabled,
						onChange: ( val ) => update( 'enabled', val ),
					} ),
				),
				el( FlexItem, null,
					el( Button, { variant: 'primary', onClick: save, isBusy: saving },
						saving ? __( 'Saving…', 'flavor-flow' ) : __( 'Save Workflow', 'flavor-flow' ) ),
				),
			),

			// Title.
			el( Card, { className: 'ff-card' },
				el( CardHeader, null, el( 'h2', null, __( 'Workflow', 'flavor-flow' ) ) ),
				el( CardBody, null,
					el( TextControl, {
						label: __( 'Workflow Name', 'flavor-flow' ),
						value: form.title,
						onChange: ( val ) => update( 'title', val ),
					} ),
				),
			),

			// Trigger.
			el( Card, { className: 'ff-card' },
				el( CardHeader, null, el( 'h2', null, __( 'Trigger', 'flavor-flow' ) ) ),
				el( CardBody, null,
					el( SelectControl, {
						label: __( 'When this happens…', 'flavor-flow' ),
						value: form.trigger,
						options: triggerOptions,
						onChange: ( val ) => update( 'trigger', val ),
					} ),
				),
			),

			// Conditions.
			el( Card, { className: 'ff-card' },
				el( CardHeader, null, el( 'h2', null, __( 'Conditions', 'flavor-flow' ) ) ),
				el( CardBody, null,
					el( SelectControl, {
						label: __( 'Logic', 'flavor-flow' ),
						value: form.conditions.logic || 'AND',
						options: [
							{ label: 'AND — all must match', value: 'AND' },
							{ label: 'OR — any can match', value: 'OR' },
						],
						onChange: ( val ) => update( 'conditions', { ...form.conditions, logic: val } ),
					} ),
					( form.conditions.rules || [] ).map( ( rule, i ) =>
						el( Flex, { key: i, className: 'ff-condition-row', gap: 2 },
							el( FlexItem, null,
								el( SelectControl, {
									label: i === 0 ? __( 'Field', 'flavor-flow' ) : '',
									value: rule.field,
									options: [ { label: '—', value: '' } ].concat(
										payloadFields.map( ( f ) => ( { label: f, value: f } ) )
									),
									onChange: ( val ) => updateCondition( i, 'field', val ),
								} ),
							),
							el( FlexItem, null,
								el( SelectControl, {
									label: i === 0 ? __( 'Type', 'flavor-flow' ) : '',
									value: rule.type,
									options: system.conditions.map( ( c ) => ( { label: c.label, value: c.slug } ) ),
									onChange: ( val ) => updateCondition( i, 'type', val ),
								} ),
							),
							el( FlexItem, null,
								el( SelectControl, {
									label: i === 0 ? __( 'Operator', 'flavor-flow' ) : '',
									value: rule.operator,
									options: ( () => {
										const cond = system.conditions.find( ( c ) => c.slug === rule.type );
										if ( ! cond ) return [];
										return Object.entries( cond.operators ).map( ( [ k, v ] ) => ( { label: v, value: k } ) );
									} )(),
									onChange: ( val ) => updateCondition( i, 'operator', val ),
								} ),
							),
							el( FlexItem, null,
								el( TextControl, {
									label: i === 0 ? __( 'Value', 'flavor-flow' ) : '',
									value: rule.value,
									onChange: ( val ) => updateCondition( i, 'value', val ),
								} ),
							),
							el( FlexItem, null,
								el( Button, {
									isSmall: true,
									isDestructive: true,
									onClick: () => removeCondition( i ),
									className: 'ff-remove-btn',
								}, '×' ),
							),
						)
					),
					el( Button, { variant: 'secondary', isSmall: true, onClick: addCondition },
						__( '+ Add Condition', 'flavor-flow' ) ),
				),
			),

			// Actions.
			el( Card, { className: 'ff-card' },
				el( CardHeader, null, el( 'h2', null, __( 'Actions', 'flavor-flow' ) ) ),
				el( CardBody, null,
					form.actions.map( ( action, i ) => {
						const actionDef = system.actions.find( ( a ) => a.slug === action.type );
						const configFields = actionDef?.config_schema?.properties || {};

						return el( Card, { key: i, className: 'ff-action-card' },
							el( CardBody, null,
								el( Flex, { gap: 2, align: 'flex-end' },
									el( FlexBlock, null,
										el( SelectControl, {
											label: __( 'Action Type', 'flavor-flow' ),
											value: action.type,
											options: actionOptions,
											onChange: ( val ) => updateAction( i, 'type', val ),
										} ),
									),
									el( FlexItem, null,
										el( Button, {
											isSmall: true,
											isDestructive: true,
											onClick: () => removeAction( i ),
										}, __( 'Remove', 'flavor-flow' ) ),
									),
								),
								Object.entries( configFields ).map( ( [ key, schema ] ) =>
									el( TextControl, {
										key: key,
										label: schema.description || key,
										value: action.config?.[ key ] || '',
										onChange: ( val ) => updateAction( i, key, val ),
										help: schema.type === 'string' ? __( 'Supports {{field}} placeholders.', 'flavor-flow' ) : '',
									} )
								),
							),
						);
					} ),
					el( Button, { variant: 'secondary', isSmall: true, onClick: addAction },
						__( '+ Add Action', 'flavor-flow' ) ),
				),
			),
		);
	}

	/* ─── Logs Page ──────────────────────────────────────────────────── */

	function LogsPage() {
		const [ logs, setLogs ] = useState( [] );
		const [ loading, setLoading ] = useState( true );
		const [ page, setPage ] = useState( 1 );
		const [ total, setTotal ] = useState( 0 );

		const load = ( p ) => {
			setLoading( true );
			rest( `/logs?per_page=25&page=${ p }` )
				.then( ( data, response ) => {
					setLogs( data );
				} )
				.catch( () => {} )
				.finally( () => setLoading( false ) );
		};

		useEffect( () => { load( page ); }, [ page ] );

		const purge = () => {
			if ( ! confirm( __( 'Delete ALL logs?', 'flavor-flow' ) ) ) return;
			rest( '/logs/purge', { method: 'DELETE' } ).then( () => load( 1 ) );
		};

		return el( Fragment, null,
			el( Flex, { className: 'ff-toolbar' },
				el( FlexBlock, null, el( 'h2', null, __( 'Execution Logs', 'flavor-flow' ) ) ),
				el( FlexItem, null,
					el( Button, { variant: 'secondary', isDestructive: true, onClick: purge },
						__( 'Purge All Logs', 'flavor-flow' ) ),
				),
			),
			loading
				? el( 'div', { className: 'ff-loading' }, el( Spinner ) )
				: el( 'table', { className: 'widefat ff-table' },
					el( 'thead', null,
						el( 'tr', null,
							el( 'th', null, __( 'ID', 'flavor-flow' ) ),
							el( 'th', null, __( 'Workflow', 'flavor-flow' ) ),
							el( 'th', null, __( 'Trigger', 'flavor-flow' ) ),
							el( 'th', null, __( 'Status', 'flavor-flow' ) ),
							el( 'th', null, __( 'Message', 'flavor-flow' ) ),
							el( 'th', null, __( 'Date', 'flavor-flow' ) ),
						),
					),
					el( 'tbody', null,
						logs.length === 0
							? el( 'tr', null, el( 'td', { colSpan: 6 }, __( 'No logs found.', 'flavor-flow' ) ) )
							: logs.map( ( log ) =>
								el( 'tr', { key: log.id },
									el( 'td', null, log.id ),
									el( 'td', null, log.workflow_id ),
									el( 'td', null, log.trigger_name ),
									el( 'td', null,
										el( 'span', { className: 'ff-badge ff-badge--' + log.status }, log.status ),
									),
									el( 'td', null, log.message ),
									el( 'td', null, log.created_at ),
								),
							),
					),
				),
			el( Flex, { className: 'ff-pagination', justify: 'center' },
				el( Button, { isSmall: true, disabled: page <= 1, onClick: () => setPage( page - 1 ) }, '← Prev' ),
				el( 'span', null, `Page ${ page }` ),
				el( Button, { isSmall: true, onClick: () => setPage( page + 1 ) }, 'Next →' ),
			),
		);
	}

	/* ─── Settings Page ──────────────────────────────────────────────── */

	function SettingsPage() {
		const [ settings, setSettings ] = useState( null );
		const [ saving, setSaving ] = useState( false );
		const [ notice, setNotice ] = useState( null );

		useEffect( () => {
			rest( '/settings' ).then( setSettings );
		}, [] );

		const save = () => {
			setSaving( true );
			rest( '/settings', { method: 'PUT', data: settings } )
				.then( ( data ) => {
					setSettings( data );
					setNotice( { status: 'success', message: __( 'Settings saved.', 'flavor-flow' ) } );
				} )
				.catch( () => {
					setNotice( { status: 'error', message: __( 'Failed to save settings.', 'flavor-flow' ) } );
				} )
				.finally( () => setSaving( false ) );
		};

		if ( ! settings ) {
			return el( 'div', { className: 'ff-loading' }, el( Spinner ) );
		}

		const update = ( key, value ) => setSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );

		return el( Fragment, null,
			notice && el( Notice, {
				status: notice.status,
				isDismissible: true,
				onRemove: () => setNotice( null ),
			}, notice.message ),

			el( Card, { className: 'ff-card' },
				el( CardHeader, null, el( 'h2', null, __( 'General Settings', 'flavor-flow' ) ) ),
				el( CardBody, null,
					el( SelectControl, {
						label: __( 'Execution Mode', 'flavor-flow' ),
						value: settings.execution_mode,
						options: [
							{ label: __( 'Asynchronous (recommended)', 'flavor-flow' ), value: 'async' },
							{ label: __( 'Synchronous', 'flavor-flow' ), value: 'sync' },
						],
						onChange: ( val ) => update( 'execution_mode', val ),
						help: __( 'Async uses the queue. Sync executes immediately during the hook.', 'flavor-flow' ),
					} ),
					el( TextControl, {
						label: __( 'Max Retries', 'flavor-flow' ),
						type: 'number',
						value: String( settings.max_retries ),
						onChange: ( val ) => update( 'max_retries', parseInt( val, 10 ) || 0 ),
					} ),
					el( TextControl, {
						label: __( 'Webhook Timeout (seconds)', 'flavor-flow' ),
						type: 'number',
						value: String( settings.webhook_timeout ),
						onChange: ( val ) => update( 'webhook_timeout', parseInt( val, 10 ) || 15 ),
					} ),
				),
			),

			el( Card, { className: 'ff-card' },
				el( CardHeader, null, el( 'h2', null, __( 'Logging', 'flavor-flow' ) ) ),
				el( CardBody, null,
					el( ToggleControl, {
						label: __( 'Enable Logging', 'flavor-flow' ),
						checked: settings.enable_logging,
						onChange: ( val ) => update( 'enable_logging', val ),
					} ),
					el( TextControl, {
						label: __( 'Log Retention (days)', 'flavor-flow' ),
						type: 'number',
						value: String( settings.log_retention ),
						onChange: ( val ) => update( 'log_retention', parseInt( val, 10 ) || 30 ),
					} ),
				),
			),

			el( Flex, { justify: 'flex-end', className: 'ff-save-bar' },
				el( Button, { variant: 'primary', onClick: save, isBusy: saving },
					saving ? __( 'Saving…', 'flavor-flow' ) : __( 'Save Settings', 'flavor-flow' ) ),
			),
		);
	}

	/* ─── License Page ───────────────────────────────────────────────── */

	function LicensePage() {
		const [ licenseKey, setLicenseKey ] = useState( '' );
		const [ status, setStatus ] = useState( null );
		const [ loading, setLoading ] = useState( true );
		const [ notice, setNotice ] = useState( null );

		useEffect( () => {
			rest( '/license/status' ).then( ( data ) => {
				setStatus( data );
				setLoading( false );
			} );
		}, [] );

		const activate = () => {
			setLoading( true );
			rest( '/license/activate', { method: 'POST', data: { license_key: licenseKey } } )
				.then( ( data ) => {
					setNotice( { status: data.success ? 'success' : 'error', message: data.message } );
					return rest( '/license/status' );
				} )
				.then( setStatus )
				.finally( () => setLoading( false ) );
		};

		const deactivate = () => {
			setLoading( true );
			rest( '/license/deactivate', { method: 'POST' } )
				.then( ( data ) => {
					setNotice( { status: 'success', message: data.message } );
					return rest( '/license/status' );
				} )
				.then( setStatus )
				.finally( () => setLoading( false ) );
		};

		if ( loading && ! status ) {
			return el( 'div', { className: 'ff-loading' }, el( Spinner ) );
		}

		return el( Fragment, null,
			notice && el( Notice, {
				status: notice.status,
				isDismissible: true,
				onRemove: () => setNotice( null ),
			}, notice.message ),

			el( Card, { className: 'ff-card' },
				el( CardHeader, null, el( 'h2', null, __( 'License', 'flavor-flow' ) ) ),
				el( CardBody, null,
					el( 'p', null,
						__( 'Status: ', 'flavor-flow' ),
						el( 'strong', null, status?.is_pro ? __( 'Pro (Active)', 'flavor-flow' ) : __( 'Lite (Free)', 'flavor-flow' ) ),
					),
					el( TextControl, {
						label: __( 'License Key', 'flavor-flow' ),
						value: licenseKey,
						onChange: setLicenseKey,
						placeholder: 'XXXX-XXXX-XXXX-XXXX',
					} ),
					el( Flex, { gap: 2 },
						el( Button, { variant: 'primary', onClick: activate, disabled: ! licenseKey },
							__( 'Activate', 'flavor-flow' ) ),
						status?.is_pro && el( Button, { variant: 'secondary', isDestructive: true, onClick: deactivate },
							__( 'Deactivate', 'flavor-flow' ) ),
					),
				),
			),

			el( Card, { className: 'ff-card ff-pro-features' },
				el( CardHeader, null, el( 'h2', null, __( 'Pro Features', 'flavor-flow' ) ) ),
				el( CardBody, null,
					el( 'ul', null,
						el( 'li', null, __( 'Unlimited workflows (Lite: 5)', 'flavor-flow' ) ),
						el( 'li', null, __( 'WooCommerce triggers & actions', 'flavor-flow' ) ),
						el( 'li', null, __( 'Advanced conditional logic (nested groups)', 'flavor-flow' ) ),
						el( 'li', null, __( 'Webhook ingress & egress', 'flavor-flow' ) ),
						el( 'li', null, __( 'Priority support', 'flavor-flow' ) ),
						el( 'li', null, __( 'Scheduled workflows (time-based triggers)', 'flavor-flow' ) ),
					),
				),
			),
		);
	}

	/* ─── Mount ──────────────────────────────────────────────────────── */

	const container = document.getElementById( 'flavor-flow-app' );
	if ( container ) {
		wp.element.render( el( App ), container );
	}
} )();

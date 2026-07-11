<?php
/**
 * Module Name: Custom Links
 * Module ID: customlinks
 * Description: Add your own custom action buttons/links to the dashboard.
 * Version: 1.0
 * Default W: 12
 * Default H: 3
 */
if (!defined('IN_GS')) { die('You cannot load this page directly.'); }

// Add $i18n_m for i18n lang files in Modules
$i18n_m = dash_module_i18n('customlinks');

// Unique ID to avoid conflicts between modules (optional)
$cl_uid = 'cl_' . substr(md5(__FILE__), 0, 6);

// -------------------------------------------------------
// Storage helpers
// This module keeps its own data file so no changes to
// Dashboard core (layout.json / Dashboard.php) are needed.
// -------------------------------------------------------
if (!function_exists('cl_data_file')) {
	function cl_data_file() {
		return GSDATAOTHERPATH . 'dashboard/customlinks.json';
	}
}

if (!function_exists('cl_get_links')) {
	function cl_get_links() {
		$file = cl_data_file();
		if (file_exists($file)) {
			$data = json_decode(file_get_contents($file), true);
			if (is_array($data)) return $data;
		}
		return [];
	}
}

if (!function_exists('cl_save_links')) {
	function cl_save_links($links) {
		$dir = GSDATAOTHERPATH . 'dashboard/';
		if (!is_dir($dir)) mkdir($dir, 0755, true);
		file_put_contents(cl_data_file(), json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}

if (!function_exists('cl_allowed_types')) {
	function cl_allowed_types() {
		return ['dark', 'blue', 'orange', 'green', 'red', 'gray'];
	}
}

if (!function_exists('cl_allowed_targets')) {
	function cl_allowed_targets() {
		return ['_self', '_blank'];
	}
}

// -------------------------------------------------------
// CSRF token
// Derived from GS's own $SALT + $USR rather than a PHP
// session, since by the time this module renders, GS has
// already sent HTML output for the page - session_start()
// is unreliable that late in the request.
// -------------------------------------------------------
global $SALT, $USR;
$cl_csrf = hash('sha256', $SALT . '|' . $USR . '|customlinks');

// -------------------------------------------------------
// Handle Save
// Plain POST + re-render (no AJAX/no core changes) - inline
// handling within this include, per GS plugin convention.
// -------------------------------------------------------
$cl_just_saved = false;

if (!empty($_POST['customlinks_save']) && isset($_POST['cl_csrf']) && hash_equals($cl_csrf, (string)$_POST['cl_csrf'])) {
	$titles  = $_POST['cl_title']  ?? [];
	$urls    = $_POST['cl_link']   ?? [];
	$types   = $_POST['cl_type']   ?? [];
	$icons   = $_POST['cl_icon']   ?? [];
	$targets = $_POST['cl_target'] ?? [];
	$allowed_types   = cl_allowed_types();
	$allowed_targets = cl_allowed_targets();

	$new_links = [];
	foreach ($titles as $i => $t) {
		$t = trim(strip_tags((string)$t));
		$u = trim((string)($urls[$i] ?? ''));
		if ($t === '' || $u === '') continue; // skip incomplete rows

		// basic safety: block javascript: / data: pseudo-protocols
		if (preg_match('/^\s*(javascript|data|vbscript)\s*:/i', $u)) continue;

		$type = (string)($types[$i] ?? 'blue');
		if (!in_array($type, $allowed_types, true)) $type = 'blue';

		$target = (string)($targets[$i] ?? '_self');
		if (!in_array($target, $allowed_targets, true)) $target = '_self';

		$icon = trim((string)($icons[$i] ?? ''));
		if ($icon !== '' && stripos($icon, '<svg') === false) $icon = '';

		$new_links[] = [
			'title'  => $t,
			'link'   => $u,
			'type'   => $type,
			'icon'   => $icon,
			'target' => $target,
		];
	}

	cl_save_links($new_links);
	$cl_just_saved = true;
}

$cl_links = cl_get_links();
global $SITEURL;
?>

<style>
#customlinks {border: none !important;}
.cl-widget-<?php echo $cl_uid; ?> { position: relative; }
.cl-widget-<?php echo $cl_uid; ?> .cl-gear {
	position: absolute; top: 0; right: 0;
	width: 26px; height: 26px;
	display: flex; align-items: center; justify-content: center;
	border-radius: 4px; cursor: pointer; color: #9aa5b1;
	background: transparent; border: none; padding: 0;
}
.cl-widget-<?php echo $cl_uid; ?> .cl-gear:hover { color: #4a90d9; background: #f0f4f9; }
.cl-widget-<?php echo $cl_uid; ?> .cl-gear svg { width: 16px; height: 16px; }

.cl-widget-<?php echo $cl_uid; ?> .cl-view { padding-right: 30px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn-row {
	display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;
}
.cl-widget-<?php echo $cl_uid; ?> .cl-btn {
	display: inline-flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
	padding: 12px 16px; min-width: 84px; border-radius: 4px;
	text-decoration: none!important; text-align: center;
	font-size: 12px; font-weight: 600; color: #fff;
	transition: opacity .15s;
}
.cl-widget-<?php echo $cl_uid; ?> .cl-btn:hover,
.cl-widget-<?php echo $cl_uid; ?> .cl-btn:visited,
.cl-widget-<?php echo $cl_uid; ?> .cl-btn:active { color: #fff; text-decoration: none!important; opacity: .85; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn svg { width: 24px; height: 24px; flex-shrink: 0; color: #fff; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn span { color: #fff; line-height: 1.2; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn-dark   { background: #2b2f38; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn-blue   { background: #2e8fe0; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn-orange { background: #e2922e; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn-green  { background: #3fae5a; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn-red    { background: #d9534f; }
.cl-widget-<?php echo $cl_uid; ?> .cl-btn-gray   { background: #7a8794; }
.cl-widget-<?php echo $cl_uid; ?> .cl-empty { color: #999; font-size: 13px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-saved-msg { color: #28a745; font-size: 12px; font-weight: 600; margin-bottom: 8px; }

.cl-widget-<?php echo $cl_uid; ?> .cl-edit { display: none; }
.cl-widget-<?php echo $cl_uid; ?>.cl-editing .cl-view { display: none; }
.cl-widget-<?php echo $cl_uid; ?>.cl-editing .cl-edit { display: block; }

.cl-widget-<?php echo $cl_uid; ?> .cl-row {
	display: flex; gap: 6px; align-items: flex-start;
	background: #f7f8fa; border: 1px solid #e3e6ea; border-radius: 4px;
	padding: 8px; margin-bottom: 8px;
}
.cl-widget-<?php echo $cl_uid; ?> .cl-row .cl-col { display: flex; flex-direction: column; gap: 2px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-row label { font-size: 10px; text-transform: uppercase; letter-spacing: .03em; color: #8a94a0; font-weight: 600; }
.cl-widget-<?php echo $cl_uid; ?> .cl-row input[type=text],
.cl-widget-<?php echo $cl_uid; ?> .cl-row select,
.cl-widget-<?php echo $cl_uid; ?> .cl-row textarea {
	border: 1px solid #d0d5dd; border-radius: 3px; padding: 4px 6px; font-size: 12px;
	background: #fff; font-family: inherit;
}
.cl-widget-<?php echo $cl_uid; ?> .cl-row textarea { font-family: monospace; resize: vertical; }
.cl-widget-<?php echo $cl_uid; ?> .cl-col-icon textarea { width: 100%; height: 32px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-col-icon   { width: 200px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-col-type   { width: 100px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-col-title  { width: 200px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-col-target { width: 100px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-col-link   { flex: 1; min-width: 140px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-col-actions { flex-direction: row; align-items: flex-end; gap: 2px; padding-top: 14px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-icon-btn {
	width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;
	border: 1px solid #d0d5dd; border-radius: 3px; background: #fff; cursor: pointer; color: #666; font-size: 13px;
}
.cl-widget-<?php echo $cl_uid; ?> .cl-icon-btn:hover { background: #eef1f4; }
.cl-widget-<?php echo $cl_uid; ?> .cl-icon-btn.cl-remove-btn { color: #d9534f; }

.cl-widget-<?php echo $cl_uid; ?> .cl-edit-actions { margin-top: 4px; display: flex; align-items: center; gap: 10px; }
.cl-widget-<?php echo $cl_uid; ?> .cl-add-btn,
.cl-widget-<?php echo $cl_uid; ?> .cl-save-btn {
	padding: 6px 14px; border-radius: 4px; border: 1px solid #d0d5dd; background: #fff;
	font-size: 12px; font-weight: 600; cursor: pointer; color: #333;
}
.cl-widget-<?php echo $cl_uid; ?> .cl-save-btn { background: #4a90d9; border-color: #4a90d9; color: #fff; }
.cl-widget-<?php echo $cl_uid; ?> .cl-save-btn:hover { background: #357abd; }
.cl-widget-<?php echo $cl_uid; ?> .cl-add-btn:hover { background: #f0f4f9; }
</style>

<div class="cl-widget-<?php echo $cl_uid; ?><?php echo (empty($cl_links) ? ' cl-editing' : ''); ?>" id="<?php echo $cl_uid; ?>">

	<button type="button" class="cl-gear" id="<?php echo $cl_uid; ?>-gear" title="<?php echo htmlspecialchars($i18n_m('cl_lang_Edit')); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
	</button>

	<!-- VIEW MODE -->
	<div class="cl-view">
		<?php if ($cl_just_saved): ?>
			<div class="cl-saved-msg">✓ <?php echo htmlspecialchars($i18n_m('cl_lang_Saved')); ?></div>
		<?php endif; ?>

		<?php if (empty($cl_links)): ?>
			<div class="cl-empty"><?php echo htmlspecialchars($i18n_m('cl_lang_No_links_view')); ?></div>
		<?php else: ?>
			<div class="cl-btn-row">
				<?php foreach ($cl_links as $link):
					$type   = in_array($link['type'] ?? '', cl_allowed_types(), true) ? $link['type'] : 'blue';
					$href   = htmlspecialchars($link['link'] ?? '#');
					$title  = htmlspecialchars($link['title'] ?? '');
					$icon   = $link['icon'] ?? '';
					// Legacy data saved before the Target option existed had no
					// 'target' key - keep those opening externally as before.
					if (isset($link['target']) && in_array($link['target'], cl_allowed_targets(), true)) {
						$target = $link['target'];
					} else {
						$target = ((strpos($link['link'] ?? '', 'http') === 0) && (strpos($link['link'] ?? '', rtrim($SITEURL, '/')) !== 0)) ? '_blank' : '_self';
					}
				?>
					<a class="cl-btn cl-btn-<?php echo $type; ?>" href="<?php echo $href; ?>"<?php echo ($target === '_blank') ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php if ($icon !== ''): ?><?php echo $icon; ?><?php endif; ?>
						<span><?php echo $title; ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- EDIT MODE -->
	<div class="cl-edit">
		<form method="post" id="<?php echo $cl_uid; ?>-form">
			<input type="hidden" name="customlinks_save" value="1">
			<input type="hidden" name="cl_csrf" value="<?php echo htmlspecialchars($cl_csrf); ?>">

			<div id="<?php echo $cl_uid; ?>-rows">
				<?php if (empty($cl_links)): ?>
					<div class="cl-empty" style="margin-bottom:8px;"><?php echo htmlspecialchars($i18n_m('cl_lang_No_links_yet')); ?></div>
				<?php else: ?>
					<?php foreach ($cl_links as $link): ?>
						<div class="cl-row">
							<div class="cl-col cl-col-icon">
								<label><?php echo htmlspecialchars($i18n_m('cl_lang_Icon_SVG')); ?> <a href="https://icon-sets.iconify.design/" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path fill="currentColor" d="M11 17H7q-2.075 0-3.537-1.463T2 12t1.463-3.537T7 7h4v2H7q-1.25 0-2.125.875T4 12t.875 2.125T7 15h4zm-3-4v-2h8v2zm5 4v-2h4q1.25 0 2.125-.875T20 12t-.875-2.125T17 9h-4V7h4q2.075 0 3.538 1.463T22 12t-1.463 3.538T17 17z"/></svg></a></label>
								<textarea name="cl_icon[]" placeholder="<?php echo htmlspecialchars($i18n_m('cl_lang_Icon_placeholder')); ?>"><?php echo htmlspecialchars($link['icon'] ?? ''); ?></textarea>
							</div>
							
							<div class="cl-col cl-col-type">
								<label><?php echo htmlspecialchars($i18n_m('cl_lang_Type')); ?></label>
								<select name="cl_type[]">
									<?php foreach (cl_allowed_types() as $t): ?>
									<option value="<?php echo $t; ?>"<?php echo (($link['type'] ?? '') === $t) ? ' selected' : ''; ?>><?php echo htmlspecialchars($i18n_m('cl_lang_Type_' . $t)); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							
							<div class="cl-col cl-col-title">
								<label><?php echo htmlspecialchars($i18n_m('cl_lang_Link_Title')); ?></label>
								<input type="text" name="cl_title[]" placeholder="<?php echo htmlspecialchars($i18n_m('cl_lang_Title_placeholder')); ?>" value="<?php echo htmlspecialchars($link['title'] ?? ''); ?>">
							</div>
							
							<div class="cl-col cl-col-link">
								<label><?php echo htmlspecialchars($i18n_m('cl_lang_Link_URL')); ?></label>
								<input type="text" name="cl_link[]" placeholder="<?php echo htmlspecialchars($i18n_m('cl_lang_Link_placeholder')); ?>" value="<?php echo htmlspecialchars($link['link'] ?? ''); ?>">
							</div>
							
							<div class="cl-col cl-col-target">
								<label><?php echo htmlspecialchars($i18n_m('cl_lang_Target')); ?></label>
								<select name="cl_target[]">
									<?php foreach (cl_allowed_targets() as $tg): ?>
									<option value="<?php echo $tg; ?>"<?php echo (($link['target'] ?? '_self') === $tg) ? ' selected' : ''; ?>><?php echo htmlspecialchars($i18n_m('cl_lang_Target_' . ($tg === '_blank' ? 'new' : 'same'))); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							
							<div class="cl-col cl-col-actions">
								<button type="button" class="cl-icon-btn cl-move-up" title="<?php echo htmlspecialchars($i18n_m('cl_lang_Move_up')); ?>">&uarr;</button>
								<button type="button" class="cl-icon-btn cl-move-down" title="<?php echo htmlspecialchars($i18n_m('cl_lang_Move_down')); ?>">&darr;</button>
								<button type="button" class="cl-icon-btn cl-remove-btn" title="<?php echo htmlspecialchars($i18n_m('cl_lang_Remove')); ?>">&times;</button>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="cl-edit-actions">
				<button type="button" class="cl-add-btn" id="<?php echo $cl_uid; ?>-add">+ <?php echo htmlspecialchars($i18n_m('cl_lang_Add_Link')); ?></button>
				<button type="submit" class="cl-save-btn"><?php echo htmlspecialchars($i18n_m('cl_lang_Save')); ?></button>
				<button type="button" class="cl-add-btn" id="<?php echo $cl_uid; ?>-done"><?php echo htmlspecialchars($i18n_m('cl_lang_Done')); ?></button>
			</div>
		</form>
	</div>
</div>

<template id="<?php echo $cl_uid; ?>-row-template">
	<div class="cl-row">
		<div class="cl-col cl-col-type">
			<label><?php echo htmlspecialchars($i18n_m('cl_lang_Type')); ?></label>
			<select name="cl_type[]">
				<?php foreach (cl_allowed_types() as $t): ?>
				<option value="<?php echo $t; ?>"><?php echo htmlspecialchars($i18n_m('cl_lang_Type_' . $t)); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="cl-col cl-col-target">
			<label><?php echo htmlspecialchars($i18n_m('cl_lang_Target')); ?></label>
			<select name="cl_target[]">
				<?php foreach (cl_allowed_targets() as $tg): ?>
				<option value="<?php echo $tg; ?>"<?php echo ($tg === '_self') ? ' selected' : ''; ?>><?php echo htmlspecialchars($i18n_m('cl_lang_Target_' . ($tg === '_blank' ? 'new' : 'same'))); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="cl-col cl-col-icon">
			<label><?php echo htmlspecialchars($i18n_m('cl_lang_Icon_SVG')); ?></label>
			<textarea name="cl_icon[]" placeholder="<?php echo htmlspecialchars($i18n_m('cl_lang_Icon_placeholder')); ?>"></textarea>
		</div>
		<div class="cl-col cl-col-title">
			<label><?php echo htmlspecialchars($i18n_m('cl_lang_Link_Title')); ?></label>
			<input type="text" name="cl_title[]" placeholder="<?php echo htmlspecialchars($i18n_m('cl_lang_Title_placeholder')); ?>">
		</div>
		<div class="cl-col cl-col-link">
			<label><?php echo htmlspecialchars($i18n_m('cl_lang_Link_URL')); ?></label>
			<input type="text" name="cl_link[]" placeholder="<?php echo htmlspecialchars($i18n_m('cl_lang_Link_placeholder')); ?>">
		</div>
		<div class="cl-col cl-col-actions">
			<button type="button" class="cl-icon-btn cl-move-up" title="<?php echo htmlspecialchars($i18n_m('cl_lang_Move_up')); ?>">&uarr;</button>
			<button type="button" class="cl-icon-btn cl-move-down" title="<?php echo htmlspecialchars($i18n_m('cl_lang_Move_down')); ?>">&darr;</button>
			<button type="button" class="cl-icon-btn cl-remove-btn" title="<?php echo htmlspecialchars($i18n_m('cl_lang_Remove')); ?>">&times;</button>
		</div>
	</div>
</template>

<script>
(function() {
	var uid = '<?php echo $cl_uid; ?>';
	var widget = document.getElementById(uid);
	if (!widget) return;

	var gear   = document.getElementById(uid + '-gear');
	var doneBtn = document.getElementById(uid + '-done');
	var addBtn  = document.getElementById(uid + '-add');
	var rows    = document.getElementById(uid + '-rows');
	var tmpl    = document.getElementById(uid + '-row-template');

	function toggleEdit() {
		widget.classList.toggle('cl-editing');
	}
	if (gear)    gear.addEventListener('click', toggleEdit);
	if (doneBtn) doneBtn.addEventListener('click', toggleEdit);

	function bindRow(row) {
		var up   = row.querySelector('.cl-move-up');
		var down = row.querySelector('.cl-move-down');
		var del  = row.querySelector('.cl-remove-btn');

		if (up) up.addEventListener('click', function() {
			var prev = row.previousElementSibling;
			if (prev) rows.insertBefore(row, prev);
		});
		if (down) down.addEventListener('click', function() {
			var next = row.nextElementSibling;
			if (next) rows.insertBefore(next, row);
		});
		if (del) del.addEventListener('click', function() {
			row.parentNode.removeChild(row);
		});
	}

	rows.querySelectorAll('.cl-row').forEach(bindRow);

	if (addBtn && tmpl) {
		addBtn.addEventListener('click', function() {
			// Remove "no links yet" placeholder if present
			var empty = rows.querySelector('.cl-empty');
			if (empty) empty.parentNode.removeChild(empty);

			var clone = tmpl.content.cloneNode(true);
			rows.appendChild(clone);
			bindRow(rows.lastElementChild);
		});
	}
})();
</script>

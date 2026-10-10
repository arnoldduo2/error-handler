<?php

/**
 * The development error page: what happened, the code where it happened (the failing part underlined in red), a link that opens that
 * file at that line in your editor, and every step of the stack with its own code. Self-contained: no stylesheet or script from a CDN,
 * so it also works offline and behind a firewall.
 *
 * Variables: $report (see Anode\ErrorHandler\Report::make()), $APP_NAME, $ROOT_PATH, $snippet_lines.
 */

use Anode\ErrorHandler\CodeFrame;

$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$r = $report;
$context = (int) ($snippet_lines ?? 6);
$tone = match (true) {
   in_array($r['severity'], ['WARNING'], true) => 'warning',
   in_array($r['severity'], ['NOTICE', 'DEPRECATED'], true) => 'info',
   default => 'danger',
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta name="robots" content="noindex">
   <title><?= $h($r['kind']) ?>: <?= $h(mb_strimwidth($r['message'], 0, 80, '…')) ?> | <?= $h($APP_NAME) ?></title>
   <style>
      :root {
         --bg: #f4f5f7; --card: #fff; --line: #e3e5e8; --text: #1a1c1f; --muted: #6b7078; --code-bg: #fbfbfc;
         --danger: #e5173f; --danger-bg: rgba(229, 23, 63, .09); --warning: #c25b16; --warning-bg: rgba(255, 119, 29, .12);
         --info: #4b57d6; --info-bg: rgba(114, 128, 253, .13); --link: #2858d6;
         --tk: #8e3ac8; --tv: #1c6bd1; --ts: #1a8a3c; --tc: #8b919a; --tn: #c25b16; --th: #6b7078;
      }
      body.dark {
         --bg: #131416; --card: #1b1d20; --line: #2c2f34; --text: #e8eaed; --muted: #9aa0a8; --code-bg: #16181b;
         --danger: #ff5c7c; --danger-bg: rgba(255, 92, 124, .13); --warning: #ffa05a; --warning-bg: rgba(255, 160, 90, .13);
         --info: #8c97ff; --info-bg: rgba(140, 151, 255, .14); --link: #7fa4ff;
         --tk: #c792ea; --tv: #82aaff; --ts: #8bd49c; --tc: #6b727c; --tn: #f2a65a; --th: #9aa0a8;
      }
      * { box-sizing: border-box; }
      body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
      a { color: var(--link); }
      .wrap { max-width: 1080px; margin: 0 auto; padding: 20px 16px 48px; }
      .top { display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 13px; margin-bottom: 14px; }
      .top button { background: none; border: 1px solid var(--line); color: var(--muted); border-radius: 6px; padding: 4px 10px; cursor: pointer; }
      .card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; margin-bottom: 16px; overflow: hidden; }
      .hero { padding: 22px 24px; border-left: 5px solid var(--tone); }
      .hero.danger { --tone: var(--danger); } .hero.warning { --tone: var(--warning); } .hero.info { --tone: var(--info); }
      .badges { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }
      .badge { font-size: 12px; font-weight: 600; letter-spacing: .04em; padding: 2px 9px; border-radius: 99px; background: var(--danger-bg); color: var(--danger); }
      .badge.warning { background: var(--warning-bg); color: var(--warning); } .badge.info { background: var(--info-bg); color: var(--info); }
      .kind { font-family: ui-monospace, "Cascadia Code", Menlo, Consolas, monospace; font-size: 13px; color: var(--muted); }
      h1 { font-size: 22px; line-height: 1.35; margin: 0 0 12px; font-weight: 600; word-break: break-word; white-space: pre-wrap; }
      .where { display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: center; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 13px; color: var(--muted); }
      .where .file { color: var(--text); }
      .btn { display: inline-block; font: 600 12px system-ui, sans-serif; text-decoration: none; padding: 4px 10px; border: 1px solid var(--line); border-radius: 6px; color: var(--link); background: var(--card); cursor: pointer; }
      .btn:hover { border-color: var(--link); }
      .head { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 9px 16px; background: var(--code-bg); border-bottom: 1px solid var(--line); font: 13px ui-monospace, Menlo, Consolas, monospace; color: var(--muted); }
      /* the code frame */
      .cf { background: var(--code-bg); overflow-x: auto; padding: 6px 0; font: 13px/1.65 ui-monospace, "Cascadia Code", "Fira Code", Menlo, Consolas, monospace; }
      .cl { display: flex; min-width: max-content; border-left: 4px solid transparent; }
      .cn { flex: none; width: 56px; padding-right: 14px; text-align: right; color: var(--tc); user-select: none; }
      .cl code { font: inherit; white-space: pre; color: var(--text); padding-right: 20px; }
      .cl-error { background: var(--danger-bg); border-left-color: var(--danger); }
      .cl-error .cn { color: var(--danger); font-weight: 700; }
      .tu { text-decoration: underline wavy var(--danger); text-decoration-thickness: 2px; text-underline-offset: 4px; background: rgba(229, 23, 63, .16); border-radius: 2px; }
      .tk { color: var(--tk); } .tv { color: var(--tv); } .ts { color: var(--ts); } .tc { color: var(--tc); font-style: italic; } .tn { color: var(--tn); } .th { color: var(--th); }
      .cf-missing { padding: 14px 18px; color: var(--muted); font-style: italic; }
      /* tabs */
      .tabs { display: flex; gap: 2px; padding: 0 10px; border-bottom: 1px solid var(--line); overflow-x: auto; }
      .tabs button { background: none; border: 0; border-bottom: 2px solid transparent; color: var(--muted); padding: 12px 14px; font: 600 14px system-ui, sans-serif; cursor: pointer; white-space: nowrap; }
      .tabs button[aria-selected="true"] { color: var(--text); border-bottom-color: var(--tone, var(--danger)); }
      .panel { display: none; } .panel.on { display: block; }
      .pad { padding: 18px 22px; }
      /* stack */
      .tools { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--line); color: var(--muted); font-size: 13px; }
      details.frame { border-bottom: 1px solid var(--line); }
      details.frame > summary { list-style: none; display: flex; gap: 12px; align-items: baseline; padding: 10px 16px; cursor: pointer; font: 13px ui-monospace, Menlo, Consolas, monospace; }
      details.frame > summary::-webkit-details-marker { display: none; }
      details.frame > summary::before { content: "▸"; color: var(--muted); }
      details.frame[open] > summary::before { content: "▾"; }
      details.frame > summary:hover { background: var(--code-bg); }
      .idx { color: var(--muted); min-width: 26px; } .loc { color: var(--text); } .ctx { color: var(--muted); margin-left: auto; text-align: right; word-break: break-all; }
      details.vendor > summary .loc { color: var(--muted); }
      .hide-vendor details.vendor { display: none; }
      .frame-body .head { border-top: 1px solid var(--line); }
      table.kv { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 18px; }
      table.kv th { text-align: left; width: 220px; color: var(--muted); font-weight: 600; padding: 6px 10px 6px 0; vertical-align: top; }
      table.kv td { padding: 6px 0; font-family: ui-monospace, Menlo, Consolas, monospace; word-break: break-all; }
      h3 { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin: 18px 0 8px; }
      h3:first-child { margin-top: 0; }
      .foot { text-align: center; color: var(--muted); font-size: 12px; margin-top: 8px; }
      .foot a { margin: 0 8px; }
   </style>
</head>

<body>
   <div class="wrap">
      <div class="top">
         <span><?= $h($APP_NAME) ?> · Anode Error Handler <?= defined('EDD_VERSION') ? $h(EDD_VERSION) : '' ?> · PHP <?= $h(PHP_VERSION) ?></span>
         <button type="button" id="theme" aria-label="Switch between light and dark">Light / dark</button>
      </div>

      <section class="card hero <?= $tone ?>">
         <div class="badges">
            <span class="badge <?= $tone ?>"><?= $h($r['severity']) ?></span>
            <span class="kind"><?= $h($r['kind']) ?></span>
            <span class="kind">#<?= $h($r['id']) ?> · <?= $h($r['time']) ?></span>
         </div>
         <h1><?= $h($r['message']) ?></h1>
         <div class="where">
            <span class="file"><?= $h($r['relative']) ?>:<?= (int) $r['line'] ?></span>
            <?php if ($r['editor']) : ?><a class="btn" href="<?= $h($r['editor']) ?>" title="Opens the file at this line in your editor">Open in editor ↗</a><?php endif ?>
            <button type="button" class="btn" data-copy="<?= $h($r['file'] . ':' . $r['line']) ?>">Copy path</button>
         </div>
      </section>

      <section class="card">
         <div class="head">
            <span><?= $h($r['relative']) ?>:<?= (int) $r['line'] ?></span>
            <?php if ($r['editor']) : ?><a class="btn" href="<?= $h($r['editor']) ?>">Open in editor ↗</a><?php endif ?>
         </div>
         <?= CodeFrame::html($r['file'], (int) $r['line'], $r['message'], $context) ?>
      </section>

      <section class="card" style="--tone: var(--<?= $tone ?>)">
         <div class="tabs" role="tablist">
            <button type="button" role="tab" aria-selected="true" data-tab="trace">Debug Trace (<?= count($r['frames']) ?>)</button>
            <button type="button" role="tab" aria-selected="false" data-tab="request">Request</button>
            <button type="button" role="tab" aria-selected="false" data-tab="env">Environment</button>
            <?php if ($r['previous']) : ?><button type="button" role="tab" aria-selected="false" data-tab="previous">Caused by (<?= count($r['previous']) ?>)</button><?php endif ?>
            <button type="button" role="tab" aria-selected="false" data-tab="help">How to fix</button>
         </div>

         <div class="panel on" id="p-trace">
            <div class="tools">
               <span>Click a step to see its code (step #0 is the code shown above). Steps inside <code>vendor/</code> are dimmed.</span>
               <label><input type="checkbox" id="hide-vendor"> Hide vendor</label>
            </div>
            <div id="frames">
               <?php foreach ($r['frames'] as $f) : ?>
                  <details class="frame <?= $f['app'] ? 'app' : 'vendor' ?>">
                     <summary>
                        <span class="idx">#<?= (int) $f['index'] ?></span>
                        <span class="loc"><?= $h($f['relative']) ?>:<?= (int) $f['line'] ?></span>
                        <span class="ctx"><?= $h($f['context']) ?></span>
                     </summary>
                     <div class="frame-body">
                        <div class="head">
                           <span><?= $h($f['relative']) ?>:<?= (int) $f['line'] ?></span>
                           <?php if ($f['editor']) : ?><a class="btn" href="<?= $h($f['editor']) ?>">Open in editor ↗</a><?php endif ?>
                        </div>
                        <?= CodeFrame::html($f['file'], (int) $f['line'], $f['index'] === 0 ? $r['message'] : '', min($context, 4)) ?>
                     </div>
                  </details>
               <?php endforeach ?>
            </div>
         </div>

         <div class="panel pad" id="p-request">
            <?php $q = $r['request']; ?>
            <?php if (!empty($q['cli'])) : ?>
               <table class="kv"><tr><th>Command</th><td><?= $h($q['command']) ?></td></tr></table>
            <?php else : ?>
               <table class="kv">
                  <tr><th>Request</th><td><?= $h($q['method']) ?> <?= $h($q['url']) ?></td></tr>
                  <tr><th>From</th><td><?= $h($q['ip']) ?><?= $q['ajax'] ? ' (ajax)' : '' ?></td></tr>
                  <tr><th>Browser</th><td><?= $h($q['agent']) ?></td></tr>
               </table>
               <?php foreach (['query' => 'Query string', 'body' => 'Form input', 'headers' => 'Headers'] as $key => $title) : ?>
                  <?php if (!empty($q[$key])) : ?>
                     <h3><?= $title ?></h3>
                     <table class="kv"><?php foreach ($q[$key] as $name => $value) : ?><tr><th><?= $h($name) ?></th><td><?= $h($value) ?></td></tr><?php endforeach ?></table>
                  <?php endif ?>
               <?php endforeach ?>
               <?php if (!empty($q['cookies'])) : ?><h3>Cookies (names only)</h3><p class="kind"><?= $h(implode(', ', $q['cookies'])) ?></p><?php endif ?>
               <p class="kind">Passwords, tokens, cookies and authorization headers are never shown.</p>
            <?php endif ?>
         </div>

         <div class="panel pad" id="p-env">
            <table class="kv">
               <?php foreach (['app' => 'Application', 'environment' => 'Environment', 'php' => 'PHP', 'sapi' => 'SAPI', 'os' => 'System', 'memory' => 'Peak memory', 'root' => 'Project folder'] as $key => $title) : ?>
                  <tr><th><?= $title ?></th><td><?= $h($r['env'][$key]) ?></td></tr>
               <?php endforeach ?>
            </table>
         </div>

         <?php if ($r['previous']) : ?>
            <div class="panel pad" id="p-previous">
               <?php foreach ($r['previous'] as $p) : ?>
                  <table class="kv">
                     <tr><th>Exception</th><td><?= $h($p['kind']) ?></td></tr>
                     <tr><th>Message</th><td><?= $h($p['message']) ?></td></tr>
                     <tr><th>Location</th><td><?= $h(\Anode\ErrorHandler\Report::relative($p['file'], $r['env']['root'])) ?>:<?= (int) $p['line'] ?></td></tr>
                  </table>
                  <?= CodeFrame::html($p['file'], (int) $p['line'], $p['message'], 3) ?>
               <?php endforeach ?>
            </div>
         <?php endif ?>

         <div class="panel pad" id="p-help">
            <p>The server hit an error it could not recover from. This page only appears in development; visitors of a production site see a plain message.</p>
            <ol>
               <li>Read the underlined code: it is the part the message is about.</li>
               <li>Open the file with <b>Open in editor</b> (set <code>editor</code> to <code>vscode</code>, <code>cursor</code>, <code>phpstorm</code> ... in the handler's options).</li>
               <li>Follow the Debug Trace upwards when the line is correct but its input is not: the step that passed the wrong value is further up.</li>
               <li>The same error, with its code and request, is in the log (<code>errors-<?= date('Y-m-d') ?>.log</code>) under id <b>#<?= $h($r['id']) ?></b>.</li>
            </ol>
         </div>
      </section>

      <div class="foot">
         <a href="<?= $h($ROOT_PATH) ?>">Home</a>
         <a href="#" onclick="history.back(); return false">Back</a>
         <a href="#" onclick="location.reload(); return false">Reload</a>
      </div>
   </div>

   <script>
      (function () {
         var body = document.body;
         if (matchMedia('(prefers-color-scheme: dark)').matches) body.classList.add('dark');
         document.getElementById('theme').addEventListener('click', function () { body.classList.toggle('dark'); });
         var tabs = document.querySelectorAll('[data-tab]');
         tabs.forEach(function (t) {
            t.addEventListener('click', function () {
               tabs.forEach(function (o) { o.setAttribute('aria-selected', o === t ? 'true' : 'false'); });
               document.querySelectorAll('.panel').forEach(function (p) { p.classList.toggle('on', p.id === 'p-' + t.getAttribute('data-tab')); });
            });
         });
         document.getElementById('hide-vendor').addEventListener('change', function (e) {
            document.getElementById('frames').classList.toggle('hide-vendor', e.target.checked);
         });
         document.querySelectorAll('[data-copy]').forEach(function (b) {
            b.addEventListener('click', function () {
               var text = b.getAttribute('data-copy');
               (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () { b.textContent = 'Copied'; }, function () { prompt('Copy:', text); });
            });
         });
      })();
   </script>
</body>

</html>

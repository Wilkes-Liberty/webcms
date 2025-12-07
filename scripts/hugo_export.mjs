#!/usr/bin/env node
// Lightweight Hugo → JSON exporter (no external deps)
// Scans ../Hugo/code content + hugo.toml and writes JSON for Drupal Migrate.

import fs from 'fs';
import path from 'path';
import crypto from 'crypto';

const projectRoot = process.cwd();
const hugoRoot = path.resolve(projectRoot, '../Hugo/code');
const contentRoot = path.join(hugoRoot, 'content');
const hugoTomlPath = path.join(hugoRoot, 'hugo.toml');
const outDir = path.resolve(projectRoot, 'web/modules/custom/wl_hugo_migrate/data');

fs.mkdirSync(outDir, { recursive: true });

function readFile(p) { return fs.readFileSync(p, 'utf8'); }
function writeJson(p, obj) { fs.writeFileSync(p, JSON.stringify(obj, null, 2)); }

function slugifySegment(seg) {
  return seg.toLowerCase().replace(/[^a-z0-9\-]+/g, '-').replace(/^-+|-+$/g, '');
}

// Minimal front matter parser (YAML subset): ---\nkey: value\nkey2: [a,b]\n---
function parseFrontMatter(src) {
  if (!src.startsWith('---')) return { data: {}, body: src };
  const end = src.indexOf('\n---', 3);
  if (end === -1) return { data: {}, body: src };
  const fm = src.slice(3, end).replace(/^\n+|\n+$/g, '');
  const body = src.slice(end + 4).replace(/^\r?\n/, '');
  const data = {};
  for (const rawLine of fm.split(/\r?\n/)) {
    const line = rawLine.trim();
    if (!line || line.startsWith('#')) continue;
    const m = line.match(/^(\w[\w_\-]*):\s*(.*)$/);
    if (!m) continue;
    const key = m[1];
    let val = m[2].trim();
    if (val.startsWith('[') && val.endsWith(']')) {
      val = val
        .slice(1, -1)
        .split(',')
        .map((s) => s.trim().replace(/^"|"$/g, ''))
        .filter(Boolean);
    } else {
      val = val.replace(/^"|"$/g, '');
    }
    data[key] = val;
  }
  return { data, body };
}

// Very small Markdown → HTML (headings, lists, paragraphs, bold/italic, links, code blocks)
function escapeHtml(s) { return s.replace(/[&<>]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }
function mdInline(s) {
  // links [text](url)
  s = s.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
  // bold then italic
  s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  s = s.replace(/\*([^*]+)\*/g, '<em>$1</em>');
  return s;
}
function markdownToHtml(md) {
  const lines = md.replace(/\r\n/g, '\n').split('\n');
  const out = [];
  let i = 0;
  let inCode = false; let codeBuf = [];
  let listMode = null; // 'ul'|'ol'
  function closeList(){ if(listMode){ out.push(`</${listMode}>`); listMode=null; } }
  while (i < lines.length) {
    let line = lines[i];
    if (line.startsWith('```')) {
      if (!inCode) { inCode = true; codeBuf = []; } else { out.push('<pre><code>' + escapeHtml(codeBuf.join('\n')) + '</code></pre>'); inCode=false; }
      i++; continue;
    }
    if (inCode) { codeBuf.push(line); i++; continue; }
    if (!line.trim()) { closeList(); i++; continue; }
    const h = line.match(/^(#{1,6})\s+(.*)$/);
    if (h) { closeList(); const level=h[1].length; out.push(`<h${level}>${mdInline(h[2])}</h${level}>`); i++; continue; }
    const ul = line.match(/^[-*]\s+(.*)$/);
    if (ul) { if(listMode!=='ul'){ closeList(); out.push('<ul>'); listMode='ul'; } out.push('<li>'+mdInline(ul[1])+'</li>'); i++; continue; }
    const ol = line.match(/^\d+\.\s+(.*)$/);
    if (ol) { if(listMode!=='ol'){ closeList(); out.push('<ol>'); listMode='ol'; } out.push('<li>'+mdInline(ol[1])+'</li>'); i++; continue; }
    const bq = line.match(/^>\s+(.*)$/);
    if (bq) { closeList(); out.push('<blockquote><p>'+mdInline(bq[1])+'</p></blockquote>'); i++; continue; }
    // paragraph: collect consecutive non-empty lines
    closeList();
    const para = [line];
    let j = i+1;
    while (j < lines.length && lines[j].trim() && !/^(#{1,6})\s+/.test(lines[j]) && !/^```/.test(lines[j]) && !/^[-*]\s+/.test(lines[j]) && !/^\d+\.\s+/.test(lines[j]) && !/^>\s+/.test(lines[j])) {
      para.push(lines[j]); j++;
    }
    out.push('<p>'+mdInline(para.join(' '))+'</p>');
    i = j;
  }
  closeList();
  return out.join('\n');
}

function uuidFromString(s) {
  const h = crypto.createHash('md5').update(s).digest('hex');
  return `${h.slice(0,8)}-${h.slice(8,12)}-${h.slice(12,16)}-${h.slice(16,20)}-${h.slice(20,32)}`;
}

function walk(dir) {
  const res = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.name.startsWith('.')) continue;
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) res.push(...walk(p));
    else if (/\.md$/i.test(entry.name)) res.push(p);
  }
  return res;
}

const sectionToVid = {
  services: 'capabilities',
  solutions: 'solutions',
  technology: 'technologies',
  industries: 'industries',
};
const pageSections = new Set(['about','capabilities','services','solutions','technology','industries']);

function buildPath(section, relSegments) {
  const base = '/' + section + '/';
  const segs = relSegments.filter(Boolean).map(slugifySegment);
  return base + (segs.length ? segs.join('/') + '/' : '');
}

// 1) Collect terms (parents first via _index.md, then leaves)
const termRecords = [];
function addTerm(vid, name, parentId, pathAlias, bodyHtml, fm) {
  const slug = pathAlias.replace(/^\//,''); // e.g., technology/content-management/
  const termId = `${vid}:${slug.replace(/\/$/, '')}`; // drop trailing slash
  termRecords.push({
    term_id: termId,
    vid,
    name,
    parent_id: parentId || null,
    path: pathAlias,
    description_html: bodyHtml || '',
    seo_title: fm?.title || name,
    meta_description: fm?.description || '',
    aliases: Array.isArray(fm?.aliases) ? fm.aliases : [],
  });
  return termId;
}

for (const section of Object.keys(sectionToVid)) {
  const secDir = path.join(contentRoot, section);
  if (!fs.existsSync(secDir)) continue;
  // parents via _index.md in subdirs
  const parentIds = new Map();
  // pass 1: directories with _index.md
  for (const entry of fs.readdirSync(secDir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      const idx = path.join(secDir, entry.name, '_index.md');
      if (fs.existsSync(idx)) {
        const { data, body } = parseFrontMatter(readFile(idx));
        const name = data.title || entry.name.replace(/[-_]/g, ' ');
        const vid = sectionToVid[section];
        const pathAlias = buildPath(section, [entry.name]);
        const termId = addTerm(vid, name, null, pathAlias, markdownToHtml(body), data);
        parentIds.set(entry.name, termId);
      }
    }
  }
  // pass 2: leaves (.md not _index.md at root or within subdirs)
  for (const file of walk(secDir)) {
    const rel = path.relative(secDir, file);
    if (/_index\.md$/i.test(file)) continue;
    const segs = rel.split(path.sep);
    const basename = segs.pop();
    const nameNoExt = basename.replace(/\.md$/i, '');
    const { data, body } = parseFrontMatter(readFile(file));
    const name = data.title || nameNoExt.replace(/[-_]/g, ' ');
    const vid = sectionToVid[section];
    let parentId = null;
    if (segs.length >= 1) {
      const parentKey = segs[segs.length-1];
      parentId = parentIds.get(parentKey) || null;
    }
    const pathAlias = buildPath(section, [...segs, nameNoExt]);
    addTerm(vid, name, parentId, pathAlias, markdownToHtml(body), data);
  }
}

// 2) Collect pages (section landings and About/*)
const pageRecords = [];
function addPage(title, pathAlias, bodyHtml, fm, dateGuess) {
  pageRecords.push({
    title: title || pathAlias,
    path: pathAlias,
    body_html: bodyHtml || '',
    seo_title: fm?.title || title || '',
    meta_description: fm?.description || '',
    date: fm?.date || dateGuess || new Date().toISOString(),
    aliases: Array.isArray(fm?.aliases) ? fm.aliases : [],
  });
}
for (const section of pageSections) {
  const secDir = path.join(contentRoot, section);
  if (!fs.existsSync(secDir)) continue;
  const idx = path.join(secDir, '_index.md');
  if (fs.existsSync(idx)) {
    const { data, body } = parseFrontMatter(readFile(idx));
    const title = data.title || section;
    const pathAlias = buildPath(section, []);
    addPage(title, pathAlias, markdownToHtml(body), data);
  }
  if (section === 'about') {
    // import about/*.md pages
    for (const entry of fs.readdirSync(secDir, { withFileTypes: true })) {
      if (entry.isFile() && entry.name.endsWith('.md') && entry.name !== '_index.md') {
        const file = path.join(secDir, entry.name);
        const { data, body } = parseFrontMatter(readFile(file));
        const nameNoExt = entry.name.replace(/\.md$/i,'');
        const title = data.title || nameNoExt.replace(/[-_]/g,' ');
        const pathAlias = buildPath('about', [nameNoExt]);
        addPage(title, pathAlias, markdownToHtml(body), data);
      }
    }
  }
}

// 3) Build menu from hugo.toml [[menu.main]]
function parseMenuToml(toml) {
  const blocks = [];
  const lines = toml.split(/\r?\n/);
  let cur = null;
  for (let raw of lines) {
    const line = raw.trim();
    if (line === '[[menu.main]]') { if (cur) blocks.push(cur); cur = {}; continue; }
    if (!cur) continue;
    const m = line.match(/^(identifier|name|url|parent|weight)\s*=\s*(.+)$/);
    if (!m) continue;
    let [, key, val] = m;
    val = val.trim();
    if (val.startsWith('"') && val.endsWith('"')) val = val.slice(1, -1);
    else if (/^\d+$/.test(val)) val = parseInt(val, 10);
    cur[key] = val;
  }
  if (cur) blocks.push(cur);
  // normalize ids and hierarchy
  const byId = new Map();
  for (const b of blocks) { if (!b.identifier) b.identifier = b.url || b.name; byId.set(b.identifier, b); b.children = []; }
  for (const b of blocks) { if (b.parent && byId.has(b.parent)) byId.get(b.parent).children.push(b); }
  return blocks.filter(b => !b.parent);
}
const menuBlocks = fs.existsSync(hugoTomlPath) ? parseMenuToml(readFile(hugoTomlPath)) : [];

const menuFlat = [];
function pushMenuItem(item, parentUuid=null) {
  const id = item.identifier || item.url || item.name;
  const uuid = uuidFromString('hugo-menu:' + id);
  menuFlat.push({ uuid, title: item.name || id, url: item.url || '/', weight: item.weight ?? 0, parent_uuid: parentUuid });
  for (const child of item.children || []) pushMenuItem(child, uuid);
}
for (const top of menuBlocks) pushMenuItem(top, null);

// 4) Redirects from aliases
const redirects = [];
for (const term of termRecords) {
  for (const a of term.aliases || []) redirects.push({ from: a, to: term.path, code: 301 });
}
for (const page of pageRecords) {
  for (const a of page.aliases || []) redirects.push({ from: a, to: page.path, code: 301 });
}

// Write files (wrapped in {items: []} for migrate_plus json parser)
writeJson(path.join(outDir, 'terms.json'), { items: termRecords });
writeJson(path.join(outDir, 'pages.json'), { items: pageRecords });
writeJson(path.join(outDir, 'menu.json'), { items: menuFlat });
writeJson(path.join(outDir, 'redirects.json'), { items: redirects });

console.log(`Exported: ${termRecords.length} terms, ${pageRecords.length} pages, ${menuFlat.length} menu links, ${redirects.length} redirects → ${outDir}`);

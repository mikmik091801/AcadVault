import { readFileSync, writeFileSync } from 'node:fs';

const root = 'c:/AcadVault/';
const out = process.argv[2];

/** Pull an exact line range out of a real file, trimming trailing blank lines. */
const slice = (file, from, to) => {
    const lines = readFileSync(root + file, 'utf8').split(/\r?\n/);
    const picked = lines.slice(from - 1, to);
    while (picked.length && picked[picked.length - 1].trim() === '') picked.pop();

    // Strip the common indentation so the block reads flush.
    const indents = picked.filter((l) => l.trim()).map((l) => l.match(/^\s*/)[0].length);
    const pad = Math.min(...indents);

    return {
        file,
        lines: `${from}\u2013${from + picked.length - 1}`,
        code: picked.map((l) => l.slice(pad)).join('\n'),
    };
};

const functions = [
    {
        n: 1,
        name: 'Role-Based Access Control (RBAC)',
        threat: 'Unauthorized Data Access',
        desc: 'Restricts every screen and action to the roles permitted to use it. Each account holds one of four roles — Administrator, Registrar, Faculty or Student — and the restriction is enforced on the server by route middleware and per-record authorization policies, not by hiding menu items. A signed-in student who types an administrator-only address directly is refused with HTTP 403, and faculty are scoped to the subjects they teach so they never see a colleague\u2019s students or grades.',
        parts: [
            { label: 'The role gate', ...slice('app/Http/Middleware/EnsureUserHasRole.php', 17, 30) },
            { label: 'Applying it to a controller', ...slice('app/Http/Controllers/UserController.php', 23, 28) },
            { label: 'Per-record ownership', ...slice('app/Policies/AcademicRecordPolicy.php', 19, 34) },
        ],
    },
    {
        n: 2,
        name: 'Field-Level Encryption at Rest (AES-256)',
        threat: 'Sensitive Data Exposure',
        desc: 'Encrypts grades and remarks before they reach the database, so the stored values are unreadable ciphertext. Anyone with direct database access — including a database administrator, or someone holding a stolen backup — learns nothing. Decryption happens only in memory, only for a user whose role permits it, and a random initialisation vector is used on every write so two identical grades never produce the same ciphertext.',
        parts: [
            { label: 'The encrypted casts', ...slice('app/Models/AcademicRecord.php', 19, 35) },
            { label: 'Decryption is an audited event', ...slice('app/Support/ExportService.php', 36, 39) },
        ],
    },
    {
        n: 3,
        name: 'QR Document Verification (SHA-256 Fingerprint)',
        threat: 'Document Forgery / Tampering',
        desc: 'Proves whether a printed document still matches the record it was issued from. At the moment of issue a SHA-256 fingerprint is taken of the record\u2019s contents and stored with a public UUID, and a QR code pointing at a public verification address is printed on the PDF. Scanning it re-computes the fingerprint from the live record and compares the two in constant time. The hash covers the record rather than the PDF file, because the PDF is regenerated on demand — hashing the file would only prove the renderer was consistent.',
        parts: [
            { label: 'Fingerprint taken at issue', ...slice('app/Support/RecordFingerprint.php', 27, 61) },
            { label: 'Compared when the QR is scanned', ...slice('app/Http/Controllers/VerificationController.php', 36, 50) },
        ],
    },
    {
        n: 4,
        name: 'Audit Trail Logging',
        threat: 'Unauthorized / Untracked Access Attempts',
        desc: 'Records every security-relevant action in an append-only log that cannot be edited or deleted from within the application. It captures sign-ins and failed sign-ins, record views and decryptions, document exports and verifications, enrollment activity, role changes, and every blocked access attempt — storing the actor, the action, the target record, the IP address and the timestamp. Blocked attempts are caught centrally, so a refusal from the role middleware and a refusal from a policy are recorded identically.',
        parts: [
            { label: 'Writing an entry', ...slice('app/Support/AuditLogger.php', 135, 168) },
            { label: 'Every 403 recorded, wherever it came from', ...slice('bootstrap/app.php', 34, 47) },
        ],
    },
    {
        n: 5,
        name: 'Credential Protection (Argon2id Hashing and Lockout)',
        threat: 'Weak Credential Compromise',
        desc: 'Passwords are never stored. They are put through Argon2id, a one-way hashing function, so nobody — not even the system owner — can recover a user\u2019s password; a forgotten one can only be reset. This is deliberately different from the encryption used on grades, which is reversible with a key. Repeated failed sign-ins from the same address trigger a lockout, and both the failures and the lockout are written to the audit log.',
        parts: [
            { label: 'One-way hashing, never encryption', ...slice('app/Models/User.php', 80, 93) },
            { label: 'The hashing driver', ...slice('config/hashing.php', 16, 16) },
            { label: 'Lockout after repeated failures', ...slice('app/Http/Requests/Auth/LoginRequest.php', 56, 78) },
        ],
    },
];

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

functions.forEach((f) =>
    f.parts.forEach((p) => console.log(`  ${String(f.n)}. ${p.file}:${p.lines}  (${p.code.split('\n').length} lines)`))
);

const blocks = functions
    .map(
        (f) => `
    <section class="fn">
      <div class="fn-head">
        <span class="fn-num">${String(f.n).padStart(2, '0')}</span>
        <div>
          <h2>${f.name}</h2>
          <p class="threat">Mitigates: ${f.threat}</p>
        </div>
      </div>

      <div class="fn-body">
        <p class="desc"><strong>Description:</strong> ${f.desc}</p>

        ${f.parts
            .map(
                (p) => `
        <div class="snip">
          <div class="snip-head">
            <span class="snip-label">${esc(p.label)}</span>
            <span class="snip-path">${esc(p.file)}<span class="ln">:${p.lines}</span></span>
            <button type="button" class="copy" data-copy>Copy</button>
          </div>
          <pre><code>${esc(p.code)}</code></pre>
        </div>`,
            )
            .join('\n')}
      </div>
    </section>`,
    )
    .join('\n');

const html = `<title>AcadVault Security Code</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=JetBrains+Mono:wght@400;500&display=swap">

<style>
  :root {
    --ground:#fbfaf7; --surface:#fff; --surface-alt:#f5f2ea; --label-bg:#eef1f6;
    --ink:#14243c; --ink-soft:#4a5468; --ink-faint:#7a8494;
    --rule:#dfe3ea; --rule-strong:#c3cad6; --accent:#a87c26; --accent-soft:#f3ead6;
    --code-bg:#fbfaf7; --code-ink:#22304a;
  }
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      --ground:#0f1622; --surface:#16202f; --surface-alt:#1b2637; --label-bg:#1d2a3d;
      --ink:#e9eef6; --ink-soft:#b3bece; --ink-faint:#8592a5;
      --rule:#2a3648; --rule-strong:#3a4761; --accent:#d8ab52; --accent-soft:#2c2618;
      --code-bg:#111a28; --code-ink:#cdd8e8;
    }
  }
  :root[data-theme="dark"] {
    --ground:#0f1622; --surface:#16202f; --surface-alt:#1b2637; --label-bg:#1d2a3d;
    --ink:#e9eef6; --ink-soft:#b3bece; --ink-faint:#8592a5;
    --rule:#2a3648; --rule-strong:#3a4761; --accent:#d8ab52; --accent-soft:#2c2618;
    --code-bg:#111a28; --code-ink:#cdd8e8;
  }

  * { box-sizing:border-box; }
  body {
    margin:0; background:var(--ground); color:var(--ink);
    font-family:"Source Serif 4",Georgia,serif; font-size:16px; line-height:1.6;
    -webkit-font-smoothing:antialiased;
  }
  .wrap { max-width:1000px; margin:0 auto; padding:48px 24px 96px; }

  .masthead { border-bottom:3px solid var(--ink); padding-bottom:22px; }
  .eyebrow {
    font-family:"Archivo",system-ui,sans-serif; font-size:11px; font-weight:700;
    letter-spacing:.18em; text-transform:uppercase; color:var(--accent); margin:0 0 10px;
  }
  h1 {
    font-family:"Archivo",system-ui,sans-serif; font-size:clamp(26px,4.4vw,38px);
    font-weight:700; letter-spacing:-.02em; line-height:1.12; margin:0 0 14px; text-wrap:balance;
  }
  .standfirst { color:var(--ink-soft); max-width:68ch; margin:0; font-size:16px; }

  .fn {
    border:1px solid var(--rule-strong); border-radius:5px;
    background:var(--surface); margin-top:26px; overflow:hidden;
  }
  .fn-head {
    display:flex; gap:14px; align-items:flex-start;
    padding:16px 20px; background:var(--label-bg); border-bottom:1px solid var(--rule);
  }
  .fn-num {
    font-family:"JetBrains Mono",monospace; font-size:13px; color:var(--accent);
    font-variant-numeric:tabular-nums; padding-top:3px;
  }
  .fn-head h2 {
    font-family:"Archivo",system-ui,sans-serif; font-size:17px; font-weight:700;
    margin:0 0 3px; letter-spacing:-.01em;
  }
  .threat {
    margin:0; font-family:"Archivo",system-ui,sans-serif; font-size:12px;
    letter-spacing:.04em; color:var(--ink-faint);
  }

  .fn-body { padding:18px 20px; }
  .desc { margin:0 0 18px; color:var(--ink-soft); font-size:15px; max-width:82ch; }
  .desc strong { color:var(--ink); font-family:"Archivo",system-ui,sans-serif; font-size:14px; }

  .snip { margin-bottom:16px; }
  .snip:last-child { margin-bottom:0; }
  .snip-head {
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    background:var(--surface-alt); border:1px solid var(--rule);
    border-bottom:0; border-radius:5px 5px 0 0; padding:8px 12px;
  }
  .snip-label {
    font-family:"Archivo",system-ui,sans-serif; font-size:12.5px; font-weight:600;
    color:var(--ink);
  }
  .snip-path {
    font-family:"JetBrains Mono",monospace; font-size:11.5px; color:var(--ink-faint);
    margin-left:auto;
  }
  .snip-path .ln { color:var(--accent); }

  .copy {
    font-family:"Archivo",system-ui,sans-serif; font-size:11.5px; font-weight:600;
    border:1px solid var(--rule-strong); background:var(--surface); color:var(--ink-soft);
    border-radius:4px; padding:3px 10px; cursor:pointer;
  }
  .copy:hover { color:var(--ink); border-color:var(--accent); }
  .copy:focus-visible { outline:2px solid var(--accent); outline-offset:2px; }
  .copy[data-done] { color:var(--accent); border-color:var(--accent); }

  pre {
    margin:0; overflow-x:auto;
    background:var(--code-bg); border:1px solid var(--rule);
    border-radius:0 0 5px 5px; padding:14px 16px;
  }
  code {
    font-family:"JetBrains Mono",monospace; font-size:12.5px; line-height:1.7;
    color:var(--code-ink); white-space:pre;
  }

  footer {
    margin-top:44px; padding-top:18px; border-top:1px solid var(--rule);
    font-size:13px; color:var(--ink-faint);
  }
</style>

<div class="wrap">
  <header class="masthead">
    <p class="eyebrow">Capstone Documentation &middot; Security Functions</p>
    <h1>AcadVault Security Code</h1>
    <p class="standfirst">
      The five security functions, each with its description and the exact source that
      implements it &mdash; file path and line numbers included. Every block was read
      straight out of the codebase, so it matches what is running. Use the Copy button,
      or select the text.
    </p>
  </header>

  ${blocks}

  <footer>
    Extracted from the AcadVault codebase on 16 September 2026. Line numbers refer to the
    files as they stand on that date.
  </footer>
</div>

<script>
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;

    const code = button.closest('.snip').querySelector('code').textContent;

    try {
      await navigator.clipboard.writeText(code);
      button.textContent = 'Copied';
    } catch {
      // Clipboard can be blocked; selecting the block still lets them copy.
      const range = document.createRange();
      range.selectNodeContents(button.closest('.snip').querySelector('code'));
      const sel = getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
      button.textContent = 'Selected';
    }

    button.dataset.done = '1';
    setTimeout(() => {
      button.textContent = 'Copy';
      delete button.dataset.done;
    }, 1600);
  });
</script>
`;

writeFileSync(out, html);
console.log('\nwritten:', Math.round(html.length / 1024), 'KB');

<?php

define('LARAVEL_START', microtime(true));

$uri = $_SERVER['REQUEST_URI'] ?? '/';

if ($uri === '/') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ERP Universal</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;background:#0f172a;color:#e2e8f0}.card{max-width:780px;padding:32px;border:1px solid #334155;border-radius:20px;background:#111827;box-shadow:0 20px 60px rgba(0,0,0,.35)}h1{margin:0 0 12px;font-size:2rem}p{line-height:1.6;margin:8px 0}a{color:#38bdf8;text-decoration:none}code{background:#0b1120;padding:2px 6px;border-radius:6px}</style></head><body><main class="card"><h1>ERP Universal</h1><p>Sistema de gestão empresarial multi-tenant online.</p><p>Teste da API: <a href="/api/ping">/api/ping</a></p><p>Login: <code>/api/auth/login</code></p><p>Idioma: pt-BR / en / es</p></main></body></html>';
    exit;
}

if ($uri === '/login') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Login - ERP Universal</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;background:#0f172a;color:#e2e8f0}.card{width:min(420px,92vw);padding:28px;border:1px solid #334155;border-radius:18px;background:#111827}label{display:block;margin-top:14px;font-size:.9rem}input{width:100%;margin-top:6px;padding:12px;border-radius:10px;border:1px solid #334155;background:#0b1120;color:#e2e8f0}button{width:100%;margin-top:18px;padding:12px;border:0;border-radius:10px;background:#38bdf8;color:#082f49;font-weight:700;cursor:pointer}small{opacity:.8}a{color:#38bdf8;text-decoration:none}</style></head><body><main class="card"><h1>Entrar</h1><p><small>Use a API em <code>/api/auth/login</code></small></p><form action="/api/auth/login" method="post"><label>E-mail<input name="email" type="email" placeholder="admin@demo.com.br"></label><label>Senha<input name="password" type="password" placeholder="Senha@123"></label><button type="submit">Entrar</button></form><p style="margin-top:14px"><a href="/">Voltar</a></p></main></body></html>';
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['error' => 'Not Found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

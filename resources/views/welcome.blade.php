<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Docs | {{ config('app.name', 'ChatBotAPI') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600" rel="stylesheet" />
    <style>
        :root {
            --bg: #0b1220;
            --bg-accent1: #102040;
            --bg-accent2: #0b1220;
            --panel: #0f1627;
            --panel-2: #0d2237;
            --border: #1f2a44;
            --muted: #9ba6be;
            --accent: #f97316;
            --text: #e5e7eb;
            --success: #22c55e;
            --info: #38bdf8;
            --danger: #ef4444;
        }

        body.light {
            --bg: #f5f7fb;
            --bg-accent1: #ffffff;
            --bg-accent2: #e8ecf5;
            --panel: #ffffff;
            --panel-2: #eef2fb;
            --border: #d9e2f2;
            --muted: #5d6475;
            --accent: #f97316;
            --text: #0f172a;
            --success: #15803d;
            --info: #0284c7;
            --danger: #b91c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
            background: radial-gradient(circle at 10% 20%, var(--bg-accent1) 0, var(--bg) 25%, var(--bg) 100%);
            color: var(--text);
            transition: background 0.3s ease, color 0.3s ease;
        }

        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        code {
            background: rgba(0, 0, 0, 0.06);
            padding: 2px 6px;
            border-radius: 6px;
            border: 1px solid var(--border);
            font-size: 0.95rem;
        }

        pre {
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 14px;
            border-radius: 12px;
            overflow: auto;
            color: var(--text);
            line-height: 1.5;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            color: #f8fafc;
        }

        p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .hero {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 18px;
            padding: 24px;
            border: 1px solid var(--border);
            background: linear-gradient(135deg, var(--panel-2) 0%, var(--panel) 100%);
            border-radius: 18px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.35);
        }

        .hero .title {
            max-width: 680px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.04);
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .eyebrow .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 5px rgba(249, 115, 22, 0.18);
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.04);
            color: var(--muted);
            font-size: 0.9rem;
        }

        .cta {
            min-width: 260px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            padding: 14px 16px;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cta .label {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .cta .base {
            font-weight: 600;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
        }

        .theme-btn {
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--panel);
            color: var(--text);
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
            display: inline-flex;
            justify-content: center;
            gap: 8px;
            align-items: center;
        }

        .theme-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .section {
            margin-top: 28px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .section h2 {
            font-size: 1.15rem;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .card {
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--panel);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
        }

        .card strong {
            color: var(--text);
        }

        .endpoint {
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--panel);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
            margin-bottom: 12px;
        }

        .endpoint-top {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 10px;
        }

        .method {
            padding: 6px 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid var(--border);
        }

        .method.get {
            color: var(--success);
            background: rgba(34, 197, 94, 0.08);
        }

        .method.post {
            color: var(--info);
            background: rgba(56, 189, 248, 0.08);
        }

        .method.delete {
            color: var(--danger);
            background: rgba(239, 68, 68, 0.08);
        }

        .path {
            font-weight: 600;
            font-size: 1.05rem;
        }

        .desc {
            color: var(--muted);
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .meta span {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
        }

        .sample {
            margin-top: 10px;
        }

        .grid-two {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 12px;
        }

        @media (max-width:700px) {
            .hero {
                flex-direction: column;
            }

            .grid-two {
                grid-template-columns: 1fr;
            }

            pre {
                font-size: 0.95rem;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="hero">
            <div class="title">
                <div class="eyebrow"><span class="dot"></span>Documentacion de API</div>
                <h1>{{ config('app.name', 'ChatBotAPI') }}</h1>
                <p>Endpoints para generar cuestionarios, resumenes, evaluaciones, materiales en Supabase, imagenes
                    educativas y temarios con modelos generativos.</p>
                <div class="badge-row">
                    <div class="badge">JSON y multipart/form-data</div>
                    <div class="badge">Respuestas en espanol</div>
                    <div class="badge">Integracion: Gemini + OpenAI + Supabase</div>
                </div>
            </div>
            <div class="cta">
                <div class="label">Base URL</div>
                <div class="base">{{ url('/api') }}</div>
                <div class="label">Version</div>
                <div>v1 (sin autenticacion)</div>
                <button id="themeToggle" class="theme-btn" type="button">Modo claro</button>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Guia rapida</h2>
            </div>
            <div class="cards">
                <div class="card">
                    <strong>Formatos</strong>
                    <p>- JSON en peticiones POST.</p>
                    <p>- multipart/form-data para subir archivos (campo <code>file</code>).</p>
                    <p>- Codificacion UTF-8.</p>
                </div>
                <div class="card">
                    <strong>Modelos y servicios</strong>
                    <p>Gemini: cuestionarios, resumenes y evaluacion de respuestas.</p>
                    <p>OpenAI: imagenes DALL-E 3 y temarios.</p>
                    <p>Supabase Storage: manejo de materiales .txt.</p>
                </div>
                <div class="card">
                    <strong>Errores</strong>
                    <p>Validaciones devuelven 422 con <code>success:false</code> y mensaje.</p>
                    <p>Fallos de servicio devuelven 500 con detalle de error.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Cuestionarios</h2>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method post">POST</span>
                    <div>
                        <div class="path">/api/cuestionarios/generar</div>
                        <div class="desc">Genera preguntas usando material proporcionado.</div>
                    </div>
                </div>
                <div class="meta"><span>Tipo: JSON</span><span>Requiere material completo</span></div>
                <div class="sample grid-two">
                    <div>
                        <div class="label">Body</div>
                        <pre>{
  "curso": "Historia",
  "tema": "Revolucion Francesa",
  "numeroPreguntas": 5,
  "tipoPreguntas": "mixed | multiple_choice | true_false | open",
  "contenidoMaterial": "texto a usar como fuente"
}</pre>
                    </div>
                    <div>
                        <div class="label">Respuesta</div>
                        <pre>{
  "curso": "...",
  "tema": "...",
  "cantidad": 5,
  "tipoPreguntas": "mixed",
  "questions": [
    {"question": "...", "type": "multiple_choice", "options": ["A","B","C","D"], "answer": "..."}
  ]
}</pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Resumenes y evaluador</h2>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method post">POST</span>
                    <div>
                        <div class="path">/api/resumenes/generar</div>
                        <div class="desc">Crea un resumen a partir del material.</div>
                    </div>
                </div>
                <div class="meta"><span>JSON</span><span>Campos: <code>tema</code>, <code>extensionParrafos</code>
                        (1-10), <code>formato</code> (simple|detallado|bullet-points),
                        <code>contenidoMaterial</code></span></div>
                <div class="sample grid-two">
                    <div>
                        <div class="label">Body</div>
                        <pre>{
  "tema": "Fotosintesis",
  "extensionParrafos": 3,
  "formato": "bullet-points",
  "contenidoMaterial": "texto..."
}</pre>
                    </div>
                    <div>
                        <div class="label">Respuesta</div>
                        <pre>{
  "tema": "Fotosintesis",
  "extensionParrafos": 3,
  "formato": "bullet-points",
  "summary": {"topic": "...", "paragraphs": ["...", "..."]}
}</pre>
                    </div>
                </div>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method post">POST</span>
                    <div>
                        <div class="path">/api/evaluador/evaluar</div>
                        <div class="desc">Evalua la respuesta de un estudiante usando el material.</div>
                    </div>
                </div>
                <div class="meta"><span>JSON</span><span>Campos: <code>temaCurso</code>, <code>pregunta</code>,
                        <code>respuestaEstudiante</code>, <code>contenidoMaterial</code></span></div>
                <div class="sample grid-two">
                    <div>
                        <div class="label">Body</div>
                        <pre>{
  "temaCurso": "Fisica basica",
  "pregunta": "Que es la velocidad?",
  "respuestaEstudiante": "descripcion...",
  "contenidoMaterial": "texto..."
}</pre>
                    </div>
                    <div>
                        <div class="label">Respuesta</div>
                        <pre>{
  "temaCurso": "...",
  "pregunta": "...",
  "respuestaEstudiante": "...",
  "evaluation": {
    "score": 0-100,
    "grade": "Excelente|Buena|Regular|Insuficiente",
    "feedback": "...",
    "strengths": ["..."],
    "improvements": ["..."]
  }
}</pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Materiales (Supabase)</h2>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method post">POST</span>
                    <div>
                        <div class="path">/api/materiales/extract-text</div>
                        <div class="desc">Extrae texto de PDF, DOCX o TXT.</div>
                    </div>
                </div>
                <div class="meta"><span>multipart/form-data</span><span>Campo: <code>file</code> (pdf|docx|txt, max
                        10MB)</span></div>
                <div class="sample">
                    <div class="label">Respuesta</div>
                    <pre>{
  "success": true,
  "text": "contenido plano",
  "filename": "archivo.pdf"
}</pre>
                </div>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method post">POST</span>
                    <div>
                        <div class="path">/api/materiales/upload</div>
                        <div class="desc">Sube un .txt a Supabase.</div>
                    </div>
                </div>
                <div class="meta"><span>multipart/form-data</span><span>Campos: <code>file</code> (.txt),
                        <code>path</code> opcional</span></div>
                <div class="sample">
                    <div class="label">Respuesta</div>
                    <pre>{
  "success": true,
  "message": "Archivo .txt subido correctamente a Supabase",
  "object_path": "cursos/cta/tema1/apuntes.txt",
  "public_url": "https://..."
}</pre>
                </div>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method get">GET</span>
                    <div>
                        <div class="path">/api/materiales/list-topics</div>
                        <div class="desc">Lista archivos .txt en una carpeta.</div>
                    </div>
                </div>
                <div class="meta"><span>Query opcional: <code>path</code></span><span>Responde <code>files:
                            ["tema1.txt", ...]</code></span></div>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method get">GET</span>
                    <div>
                        <div class="path">/api/materiales/list-topics-with-content</div>
                        <div class="desc">Lista archivos .txt con su contenido.</div>
                    </div>
                </div>
                <div class="meta"><span>Query opcional: <code>path</code></span><span>Responde <code>files: [{ "name":
                            "...", "content": "..." }]</code></span></div>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method delete">DELETE</span>
                    <div>
                        <div class="path">/api/materiales/delete</div>
                        <div class="desc">Elimina un archivo .txt del bucket.</div>
                    </div>
                </div>
                <div class="meta"><span>JSON</span><span>Campo: <code>nombre</code> (string)</span></div>
                <div class="sample">
                    <div class="label">Respuesta</div>
                    <pre>{
  "success": true,
  "message": "Archivo 'nombre.txt' eliminado correctamente."
}</pre>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Imagenes y temarios</h2>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method post">POST</span>
                    <div>
                        <div class="path">/api/images/generate</div>
                        <div class="desc">Genera una imagen con DALL-E 3 en base a un tema.</div>
                    </div>
                </div>
                <div class="meta"><span>JSON</span><span>Campos: <code>tema</code>, <code>descripcion</code></span>
                </div>
                <div class="sample">
                    <div class="label">Respuesta</div>
                    <pre>{
  "success": true,
  "tema": "...",
  "descripcion": "...",
  "image_base64": "..."
}</pre>
                </div>
            </div>
            <div class="endpoint">
                <div class="endpoint-top">
                    <span class="method post">POST</span>
                    <div>
                        <div class="path">/api/courses/generate-topics</div>
                        <div class="desc">Propone temario a partir de titulo, descripcion y nivel educativo.</div>
                    </div>
                </div>
                <div class="meta"><span>JSON</span><span>Campos: <code>titulo</code>, <code>descripcion</code>,
                        <code>nivelEducativo</code></span></div>
                <div class="sample">
                    <div class="label">Respuesta</div>
                    <pre>{
  "success": true,
  "titulo": "...",
  "descripcion": "...",
  "nivelEducativo": "...",
  "data": {
    "course_title": "...",
    "education_level": "...",
    "topics": [
      {"title": "...", "objective": "...", "estimated_sessions": 2, "summary": "..."}
    ]
  }
}</pre>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Notas utiles</h2>
            </div>
            <div class="cards">
                <div class="card">
                    <strong>Idioma</strong>
                    <p>Los prompts y respuestas se generan en espanol; para otro idioma indica en tu cuerpo de
                        solicitud.</p>
                </div>
                <div class="card">
                    <strong>Fuentes de verdad</strong>
                    <p>Gemini usa exclusivamente el texto en <code>contenidoMaterial</code>; envia material completo
                        para evitar alucinaciones.</p>
                </div>
                <div class="card">
                    <strong>Supabase</strong>
                    <p>Configura <code>SUPABASE_URL</code>, <code>SUPABASE_API_KEY</code> y <code>SUPABASE_BUCKET</code>
                        en .env para listar y subir archivos.</p>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    (() => {
        const body = document.body;
        const btn = document.getElementById('themeToggle');
        const saved = localStorage.getItem('theme');
        if (saved === 'light') body.classList.add('light');
        const updateLabel = () => {
            const light = body.classList.contains('light');
            btn.textContent = light ? 'Modo oscuro' : 'Modo claro';
        };
        updateLabel();
        btn.addEventListener('click', () => {
            body.classList.toggle('light');
            const theme = body.classList.contains('light') ? 'light' : 'dark';
            localStorage.setItem('theme', theme);
            updateLabel();
        });
    })();
</script>

</html>

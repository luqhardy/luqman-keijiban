<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Infinite Keijiban</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            overflow: hidden;
            background-color: #f0f0f0;
            cursor: grab;
        }

        body.grabbing {
            cursor: grabbing;
        }

        #canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            transform-origin: 0 0;
            will-change: transform;
        }

        .post {
            position: absolute;
            background: #fffbe6;
            border: 1px solid #d4d4d4;
            padding: 10px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 300px;
            font-family: 'Courier New', Courier, monospace;
            user-select: none;
        }

        #ui {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
    </style>
</head>

<body class="antialiased">
    <div id="canvas">
        <!-- Posts will be injected here -->
    </div>

    <div id="ui">
        <div class="flex flex-col gap-2 mb-4">
            <button id="zoom-in" class="bg-white p-2 rounded shadow hover:bg-gray-100 font-bold">+</button>
            <button id="zoom-out" class="bg-white p-2 rounded shadow hover:bg-gray-100 font-bold">-</button>
        </div>
        <form id="post-form" class="flex flex-col gap-2">
            <textarea id="content" name="content" placeholder="Write something..."
                class="border p-2 rounded w-64 h-24 resize-none" required></textarea>
            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 transition">Post</button>
        </form>
    </div>

    <script>
        const canvas = document.getElementById('canvas');
        const body = document.body;
        let isDragging = false;
        let startX, startY;
        let translateX = 0, translateY = 0;
        let scale = 1;
        const MIN_SCALE = 0.1;
        const MAX_SCALE = 5;

        // Panning Logic
        body.addEventListener('mousedown', (e) => {
            if (e.target.closest('#ui')) return; // Don't drag if clicking UI
            isDragging = true;
            body.classList.add('grabbing');
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
        });

        body.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            updateCanvasTransform();
        });

        body.addEventListener('mouseup', () => {
            isDragging = false;
            body.classList.remove('grabbing');
        });

        body.addEventListener('mouseleave', () => {
            isDragging = false;
            body.classList.remove('grabbing');
        });

        // Zoom Logic
        body.addEventListener('wheel', (e) => {
            e.preventDefault();
            const zoomSensitivity = 0.001;
            const delta = -e.deltaY * zoomSensitivity;
            const newScale = Math.min(Math.max(scale + delta, MIN_SCALE), MAX_SCALE);

            // Zoom towards mouse pointer
            // Calculate mouse position relative to canvas
            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            // Adjust translate to keep mouse point stable
            // This is a bit complex with simple translate/scale, 
            // simpler approach: just zoom center or allow drift for now to keep it simple
            // Better approach for "infinite canvas":
            // 1. Get world coordinates of mouse before zoom
            // 2. Zoom
            // 3. Adjust translate so world coordinates of mouse are same

            // Simplified "Google Maps" style zoom often requires more complex matrix math.
            // Let's stick to simple center zoom or just scale update for MVP unless requested otherwise.
            // Actually, let's try to do it right-ish.

            const scaleRatio = newScale / scale;
            // translateX = e.clientX - (e.clientX - translateX) * scaleRatio;
            // translateY = e.clientY - (e.clientY - translateY) * scaleRatio;

            scale = newScale;
            updateCanvasTransform();
        }, { passive: false });

        document.getElementById('zoom-in').addEventListener('click', () => {
            scale = Math.min(scale * 1.2, MAX_SCALE);
            updateCanvasTransform();
        });

        document.getElementById('zoom-out').addEventListener('click', () => {
            scale = Math.max(scale / 1.2, MIN_SCALE);
            updateCanvasTransform();
        });

        function updateCanvasTransform() {
            canvas.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        }

        // Fetch and Render Posts
        async function fetchPosts() {
            const response = await fetch('/api/posts');
            const posts = await response.json();
            posts.forEach(post => renderPost(post));
        }

        function renderPost(post) {
            const el = document.createElement('div');
            el.className = 'post';
            el.style.left = `${post.x}px`;
            el.style.top = `${post.y}px`;
            el.innerText = post.content;
            canvas.appendChild(el);
        }

        // Post Submission
        const form = document.getElementById('post-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = document.getElementById('content').value;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('/api/posts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ content })
                });

                if (response.ok) {
                    const newPost = await response.json();
                    renderPost(newPost);
                    document.getElementById('content').value = '';

                    // Optional: Center view on new post? 
                    // For now, let's just let it appear randomly.
                    // Maybe show a notification?
                } else {
                    alert('Failed to post');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Initial Load
        fetchPosts();
    </script>
</body>

</html>
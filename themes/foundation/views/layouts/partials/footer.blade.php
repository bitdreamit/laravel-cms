<footer>
    <div class="container">
        <div>
            <h4>{{ tenant()?->name ?? config('cms.name') }}</h4>
            <p>{{ tenant()?->name ?? 'CMS V4' }} — Modern multi-tenant content platform.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/blog">Blog</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </div>
        <div>
            <h4>Resources</h4>
            <ul>
                <li><a href="/docs">Documentation</a></li>
                <li><a href="/api">API</a></li>
                <li><a href="/privacy">Privacy</a></li>
                <li><a href="/terms">Terms</a></li>
            </ul>
        </div>
        <div>
            <h4>Connect</h4>
            <ul>
                <li><a href="#">Twitter</a></li>
                <li><a href="#">LinkedIn</a></li>
                <li><a href="#">GitHub</a></li>
            </ul>
        </div>
        <div class="copyright">
            <p>&copy; {{ date('Y') }} {{ tenant()?->name ?? config('cms.name') }}. All rights reserved.</p>
        </div>
    </div>
</footer>

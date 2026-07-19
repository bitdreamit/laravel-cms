@extends('admin.layouts.app')
@section('page-title', 'RAG Playground')

@section('content')
<div class="card">
    <h2>AI RAG Playground</h2>
    <p style="margin:1em 0; color:#64748b;">Ask a question — the system retrieves relevant published entries and generates an answer with citations.</p>
    <div class="form-group">
        <label>Question:</label>
        <textarea id="question" rows="3" placeholder="What services does this company offer?"></textarea>
    </div>
    <button onclick="askQuestion()" class="btn">Ask AI</button>
</div>

<div class="card" id="answer-card" style="display:none;">
    <h3>Answer</h3>
    <div id="answer" style="margin-top:1em; line-height:1.6;"></div>
    <div id="citations" style="margin-top:1.5em;"></div>
    <div id="latency" style="margin-top:1em; color:#64748b; font-size:0.85em;"></div>
</div>

<script>
function askQuestion() {
    const question = document.getElementById('question').value.trim();
    if (!question) return;

    const card = document.getElementById('answer-card');
    const answer = document.getElementById('answer');
    const citations = document.getElementById('citations');
    const latency = document.getElementById('latency');

    card.style.display = 'block';
    answer.innerHTML = 'Thinking...';
    citations.innerHTML = '';
    latency.innerHTML = '';

    fetch('/admin/rag/ask', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ question }),
    })
    .then(r => r.json())
    .then(data => {
        answer.innerHTML = data.answer || 'No answer.';
        if (data.citations && data.citations.length > 0) {
            citations.innerHTML = '<strong>Sources:</strong><ul>' +
                data.citations.map(c => `<li><a href="${c.url}">${c.entry_title}</a> (similarity: ${(c.similarity * 100).toFixed(1)}%)</li>`).join('') +
                '</ul>';
        }
        latency.innerHTML = `Latency: ${data.latency_ms}ms`;
    })
    .catch(e => {
        answer.innerHTML = 'Error: ' + e.message;
    });
}
</script>
@endsection

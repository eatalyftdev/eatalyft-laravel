@extends('layouts.public')

@section('title', __('Contact - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Contact Us') }}</h1>
        <p>{{ __('Have questions? We\'d love to hear from you.') }}</p>
    </div>
</header>

<section class="service-detail" aria-labelledby="contact-heading">
    <div class="container">
        <div class="contact-form">
            <form method="POST" action="{{ route('contact.store') }}" id="contact-form">
                @csrf
                <input type="hidden" name="cf-turnstile-response" id="cf-turnstile-response">
                <div id="cf-turnstile" style="margin-bottom: var(--space-4);"></div>
                
                <div class="form-group">
                    <label for="name">{{ __('Name') }}</label>
                    <input type="text" id="name" name="name" required autocomplete="name" value="{{ old('name') }}">
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="email">{{ __('Email') }}</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="{{ old('email') }}">
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="type">{{ __('Inquiry type') }}</label>
                    <select id="type" name="type" required>
                        <option value="general" {{ old('type') === 'general' ? 'selected' : '' }}>{{ __('General inquiry') }}</option>
                        <option value="support" {{ old('type') === 'support' ? 'selected' : '' }}>{{ __('Technical support') }}</option>
                        <option value="partnership" {{ old('type') === 'partnership' ? 'selected' : '' }}>{{ __('Partnership') }}</option>
                        <option value="press" {{ old('type') === 'press' ? 'selected' : '' }}>{{ __('Press / Media') }}</option>
                        <option value="billing" {{ old('type') === 'billing' ? 'selected' : '' }}>{{ __('Billing') }}</option>
                    </select>
                    @error('type')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="message">{{ __('Message') }}</label>
                    <textarea id="message" name="message" required rows="6">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" class="form-submit" disabled id="submit-btn">
                    {{ __('Sending...') }}
                </button>
            </form>
        </div>
        
        <div style="margin-top: var(--space-12); padding-top: var(--space-8); border-top: 1px solid var(--color-border);">
            <h3>{{ __('Other ways to reach us') }}</h3>
            <div class="grid grid-3" style="margin-top: var(--space-6);">
                <div>
                    <h4>{{ __('Email') }}</h4>
                    <p><a href="mailto:support@eatalyft.com">support@eatalyft.com</a></p>
                    <p><a href="mailto:partnerships@eatalyft.com">partnerships@eatalyft.com</a></p>
                </div>
                <div>
                    <h4>{{ __('Phone') }}</h4>
                    <p><a href="tel:+15551234567">+1 (555) 123-4567</a></p>
                    <p>{{ __('Mon-Fri 9am-6pm EST') }}</p>
                </div>
                <div>
                    <h4>{{ __('Address') }}</h4>
                    <p>{{ __('123 Travel St, Suite 100') }}</p>
                    <p>{{ __('San Francisco, CA 94102') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
    let turnstileWidgetId;
    
    function onTurnstileLoad() {
        turnstileWidgetId = turnstile.render('#cf-turnstile', {
            sitekey: '{{ config("services.turnstile.sitekey") }}',
            theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light',
            callback: function(token) {
                document.getElementById('cf-turnstile-response').value = token;
                document.getElementById('submit-btn').disabled = false;
            },
            'expired-callback': function() {
                document.getElementById('submit-btn').disabled = true;
            }
        });
    }
    
    // Theme change handler for Turnstile
    const observer = new MutationObserver(() => {
        if (turnstileWidgetId !== undefined) {
            turnstile.reset(turnstileWidgetId);
        }
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    
    // Form validation
    document.getElementById('contact-form')?.addEventListener('submit', function(e) {
        const token = document.getElementById('cf-turnstile-response').value;
        if (!token) {
            e.preventDefault();
            alert('{{ __('Please complete the security check.') }}');
        }
    });
</script>
@endpush
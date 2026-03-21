<x-email.layout :title="$subject ?? 'New Message'" headerText="UPDATE FROM US">
    <div style="color: #334155; font-size: 16px; line-height: 1.7; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
        {!! nl2br(e($body)) !!}
    </div>
</x-email.layout>

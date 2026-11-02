let audioContext = null;

function context() {
    if (typeof window === 'undefined') {
        return null;
    }

    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) {
        return null;
    }

    if (!audioContext) {
        audioContext = new AudioContext();
    }

    return audioContext;
}

export function unlockNotificationSound() {
    const ctx = context();
    if (ctx && ctx.state === 'suspended') {
        ctx.resume().catch(() => {});
    }
}

export function playNotificationSound() {
    const ctx = context();
    if (!ctx) {
        return;
    }

    unlockNotificationSound();

    const now = ctx.currentTime;
    // Energetic chime chord for delivery alerts (C5 -> E5 -> G5 -> C6)
    const tones = [
        { freq: 523.25, time: 0, dur: 0.18 },
        { freq: 659.25, time: 0.12, dur: 0.18 },
        { freq: 783.99, time: 0.24, dur: 0.20 },
        { freq: 1046.50, time: 0.36, dur: 0.35 },
    ];

    tones.forEach(({ freq, time, dur }) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, now + time);
        gain.gain.setValueAtTime(0.0001, now + time);
        gain.gain.exponentialRampToValueAtTime(0.35, now + time + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + time + dur);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now + time);
        osc.stop(now + time + dur + 0.02);
    });
}

export async function requestBrowserNotificationPermission() {
    if (typeof window === 'undefined' || !('Notification' in window)) {
        return false;
    }

    if (window.Notification.permission === 'granted') {
        return true;
    }

    if (window.Notification.permission === 'denied') {
        return false;
    }

    const permission = await window.Notification.requestPermission();
    return permission === 'granted';
}

export function showBrowserNotification(title, body) {
    if (typeof window === 'undefined' || !('Notification' in window)) {
        return;
    }

    if (window.Notification.permission !== 'granted') {
        return;
    }

    try {
        new window.Notification(title, {
            body,
            dir: 'rtl',
            lang: 'ar',
        });
    } catch {
        // Notification is a best-effort enhancement; polling remains authoritative.
    }
}

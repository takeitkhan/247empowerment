<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/livekit-token.php';

function mm_livekit_room_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'name' => 'default-room',
        'event_id' => 0,
    ), $atts, 'mm_room' );

    $room_name = sanitize_text_field( $atts['name'] );
    
    $current_user = wp_get_current_user();
    $identity     = $current_user->exists() ? 'user_' . $current_user->ID : 'guest_' . wp_rand( 1000, 9999 );
    $display_name = $current_user->exists() ? $current_user->display_name : 'Guest User';
    $current_user_id = $current_user->exists() ? intval( $current_user->ID ) : 0;

    // Determine if current user is host for given event (if provided)
    $is_host = false;
    $show_start_button = false;
    if ( ! empty( $atts['event_id'] ) ) {
        $host_id = get_post_meta( intval( $atts['event_id'] ), '_mm_livekit_host_id', true );
        $is_host = $host_id && intval( $host_id ) === $current_user_id;
        $is_active = get_post_meta( intval( $atts['event_id'] ), '_mm_livekit_active', true ) === '1';
        $is_scheduled = get_post_meta( intval( $atts['event_id'] ), '_mm_livekit_scheduled', true ) === '1';
        if ( $is_host && $is_scheduled && ! $is_active ) {
            $show_start_button = true;
        }
    }

    $token  = mm_generate_livekit_token( $room_name, $identity, $display_name );
    $ws_url = MM_LIVEKIT_HOST;

    ob_start();
    ?>
    <div id="mm-livekit-wrapper" style="max-width: 1200px; margin: 20px auto; background: #0f1720; border-radius: 10px; overflow: hidden; color: #e6eef8; font-family: system-ui, -apple-system, sans-serif;">
        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 0;">
            <!-- Left: Attendee list -->
            <aside id="mm-attendees" style="background:#0b1220; padding: 12px; border-right: 1px solid rgba(255,255,255,0.03); min-height: 520px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <strong>Attendees</strong>
                    <span id="mm-participant-count" style="font-size:13px; color:#9fb3d3;">0</span>
                </div>
                <div id="mm-attendee-list" style="display:flex; flex-direction:column; gap:8px; max-height:440px; overflow:auto; padding-right:6px;"></div>

                <div id="mm-broadcast-controls" style="margin-top:12px; border-top:1px dashed rgba(255,255,255,0.03); padding-top:10px;">
                    <div style="font-size:13px; color:#9fb3d3; margin-bottom:6px;">Broadcast (Host only)</div>
                    <input id="mm-rtmp-input" type="text" placeholder="rtmp://a.rtmp.youtube.com/live2/STREAM_KEY" style="width:100%; padding:8px; border-radius:6px; border:1px solid rgba(255,255,255,0.04); background:#071025; color:#fff;" />
                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <button id="mm-save-broadcast" style="flex:1; padding:8px; background:#1f6feb; border:none; border-radius:6px; color:#fff;">Save</button>
                        <button id="mm-start-broadcast" style="flex:1; padding:8px; background:#c0392b; border:none; border-radius:6px; color:#fff;">Start</button>
                    </div>
                    <div id="mm-broadcast-status" style="font-size:12px; color:#9fb3d3; margin-top:8px;"></div>
                </div>
            </aside>

            <!-- Right: Conference area -->
            <main style="padding: 14px 16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <div style="font-weight:600;">Room: <?php echo esc_html( $room_name ); ?></div>
                    <div id="mm-connection-status" style="font-size:13px; color:#9fb3d3;">Not connected</div>
                </div>

                <div id="mm-video-area" style="background:#000; border-radius:8px; min-height:360px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                    <div id="mm-local-placeholder" style="color:#7b94b3;">Meeting will appear here after joining.</div>
                </div>

                <!-- Controls -->
                <div style="display:flex; justify-content:center; gap:10px; margin-top:12px;">
                    <button id="mm-join-btn" style="padding:10px 16px; background:#1f6feb; border-radius:8px; border:none; color:#fff;">Join Meeting</button>
                    <?php if ( $show_start_button ): ?>
                        <button id="mm-start-meeting" style="padding:10px 16px; background:#27ae60; border-radius:8px; border:none; color:#fff;">Start Meeting</button>
                    <?php endif; ?>
                    <button id="mm-mic-btn" style="padding:10px 16px; background:#333; border-radius:8px; border:none; color:#fff;">Mute Mic</button>
                    <button id="mm-cam-btn" style="padding:10px 16px; background:#333; border-radius:8px; border:none; color:#fff;">Stop Cam</button>
                    <button id="mm-leave-btn" style="padding:10px 16px; background:#c0392b; border-radius:8px; border:none; color:#fff;">Leave</button>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>
    <script>
    (function(){
        const wsUrl = "<?php echo esc_js( $ws_url ); ?>";
        const token = "<?php echo esc_js( $token ); ?>";
        const roomName = "<?php echo esc_js( $room_name ); ?>";
        const currentUserName = "<?php echo esc_js( $display_name ); ?>";

        const joinBtn = document.getElementById('mm-join-btn');
        const micBtn = document.getElementById('mm-mic-btn');
        const camBtn = document.getElementById('mm-cam-btn');
        const leaveBtn = document.getElementById('mm-leave-btn');
        const statusEl = document.getElementById('mm-connection-status');
        const attendeeList = document.getElementById('mm-attendee-list');
        const participantCount = document.getElementById('mm-participant-count');
        const videoArea = document.getElementById('mm-video-area');
        const broadcastInput = document.getElementById('mm-rtmp-input');
        const saveBroadcast = document.getElementById('mm-save-broadcast');
        const startBroadcast = document.getElementById('mm-start-broadcast');
        const broadcastStatus = document.getElementById('mm-broadcast-status');

        let room = null;
        let reserved = false;

        async function ajax( action, data ){
            data = data || {};
            data.action = action;
            data.nonce = MM_LIVEKIT_AJAX.nonce;
            const res = await fetch( MM_LIVEKIT_AJAX.ajax_url, { method: 'POST', credentials: 'same-origin', headers: { 'Accept':'application/json' }, body: new URLSearchParams(data) } );
            return res.json();
        }

        async function reserveSlot(){
            const r = await ajax('mm_reserve_slot',{ room: roomName });
            if ( r.success ) {
                reserved = true;
                participantCount.innerText = r.data.count + '/' + r.data.max;
                return true;
            }
            alert(r.data || 'Room is full');
            return false;
        }

        async function releaseSlot(){
            if (!reserved) return;
            await ajax('mm_release_slot',{ room: roomName });
            reserved = false;
        }

        function addAttendee(identity, displayName){
            let id = 'att-' + identity;
            if ( document.getElementById(id) ) return;
            const el = document.createElement('div');
            el.id = id;
            el.style.cssText = 'display:flex; align-items:center; gap:8px; padding:6px; background:rgba(255,255,255,0.01); border-radius:6px;';
            el.innerHTML = '<div style="width:36px;height:36px;border-radius:50%;background:#17324a;display:flex;align-items:center;justify-content:center;font-weight:600;color:#bcd">' + (displayName.charAt(0)||'?') + '</div><div style="flex:1;">'+ displayName +'</div>';
            attendeeList.appendChild(el);
            participantCount.innerText = attendeeList.children.length;
        }

        function removeAttendee(identity){
            const id = 'att-' + identity; const el = document.getElementById(id); if (el) el.remove(); participantCount.innerText = attendeeList.children.length;
        }

        joinBtn.addEventListener('click', async function(){
            joinBtn.disabled = true; joinBtn.innerText = 'Checking...';

            // If this shortcode is tied to an event, check meeting status first
            const eventId = '<?php echo intval( isset($atts['event_id']) ? $atts['event_id'] : 0 ); ?>';
            if ( eventId && parseInt(eventId) > 0 ) {
                const status = await ajax('mm_check_meeting_status', { event_id: eventId });
                if ( status && status.success ) {
                    const data = status.data || {};
                    const isActive = data.active == 1;
                    const currentUserId = <?php echo intval( get_current_user_id() ); ?>;
                    const isHost = data.host_id && (parseInt(data.host_id) === parseInt(currentUserId));
                    const scheduled = data.scheduled == 1;
                    const scheduledFor = parseInt(data.scheduled_for) || 0;

                    if ( scheduled && ! isActive && ! isHost ) {
                        const when = scheduledFor > 0 ? new Date(scheduledFor * 1000) : null;
                        alert('This meeting is scheduled to start at: ' + (when ? when.toLocaleString() : 'soon') + '. Only the host can start it earlier.');
                        joinBtn.disabled = false; joinBtn.innerText = 'Join Meeting';
                        return;
                    }
                }
            }

            joinBtn.innerText = 'Joining...';
            const ok = await reserveSlot();
            if (!ok) { joinBtn.disabled = false; joinBtn.innerText = 'Join Meeting'; return; }

            try{
                room = new LiveKit.Room({ adaptiveStream:true, dynacast:true });
                await room.connect(wsUrl, token);
                statusEl.innerText = 'Connected';
                statusEl.style.color = '#7ee0a6';
                videoArea.innerHTML = '';

                // render local tracks
                room.localParticipant.tracks.forEach(pub => { if (pub.track && pub.track.kind === 'video') {
                    const el = pub.track.attach(); el.style.cssText='width:100%;height:100%;object-fit:cover;'; videoArea.appendChild(el);
                } });

                // when participants present
                room.participants.forEach(p => addAttendee(p.identity,p.name||p.identity));
                room.on(LiveKit.RoomEvent.ParticipantConnected, p => addAttendee(p.identity,p.name||p.identity));
                room.on(LiveKit.RoomEvent.ParticipantDisconnected, p => removeAttendee(p.identity));

                // subscribe to new tracks
                room.on(LiveKit.RoomEvent.TrackPublished, () => {});
                room.on(LiveKit.ParticipantEvent.TrackSubscribed, (track, publication, participant) => {
                    if (track.kind === 'video'){
                        const el = track.attach(); el.style.cssText='width:100%;height:100%;object-fit:cover;'; videoArea.appendChild(el);
                    }
                    if (track.kind === 'audio'){
                        const audioEl = track.attach(); audioEl.style.display='none'; document.body.appendChild(audioEl);
                    }
                });

                // request camera/mic (video optional)
                try{ await room.localParticipant.enableCameraAndMicrophone(); }catch(e){ console.warn('Camera/mic not granted',e); }

                micBtn.addEventListener('click', async ()=>{ const m = !room.localParticipant.isMicrophoneEnabled; await room.localParticipant.setMicrophoneEnabled(m); micBtn.innerText = m? 'Mute Mic':'Unmute Mic'; });
                camBtn.addEventListener('click', async ()=>{ const c = !room.localParticipant.isCameraEnabled; await room.localParticipant.setCameraEnabled(c); camBtn.innerText = c? 'Stop Cam':'Start Cam'; });
                leaveBtn.addEventListener('click', async ()=>{ await room.disconnect(); await releaseSlot(); window.location.reload(); });

            }catch(err){ console.error(err); alert('Could not join room'); await releaseSlot(); }
        });

        // Start meeting early (host)
        const startMeetingBtn = document.getElementById('mm-start-meeting');
        if ( startMeetingBtn ) {
            startMeetingBtn.addEventListener('click', async function(){
                startMeetingBtn.disabled = true; startMeetingBtn.innerText = 'Starting...';
                const eventId = '<?php echo intval( isset($atts['event_id']) ? $atts['event_id'] : 0 ); ?>';
                const r = await ajax('mm_create_meeting', { event_id: eventId, room_name: '' });
                if ( r && r.success ) {
                    alert('Meeting started');
                    window.location.reload();
                } else {
                    alert('Could not start meeting'); startMeetingBtn.disabled = false; startMeetingBtn.innerText = 'Start Meeting';
                }
            });
        }

        window.addEventListener('beforeunload', async ()=>{ await releaseSlot(); });

        // Broadcast save
        saveBroadcast.addEventListener('click', async ()=>{
            const r = await ajax('mm_save_broadcast',{ event_id: '<?php echo intval( isset($atts['event_id']) ? $atts['event_id'] : 0 ); ?>', rtmp_url: broadcastInput.value, enabled: true });
            if (r.success) broadcastStatus.innerText = 'Saved'; else broadcastStatus.innerText = 'Failed to save';
        });

        // Start broadcast is a placeholder: actual RTMP start requires server-side LiveKit control (not implemented here).
        startBroadcast.addEventListener('click', ()=>{
            broadcastStatus.innerText = 'Starting broadcast (server-side start not implemented)';
        });

    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mm_room', 'mm_livekit_room_shortcode' );
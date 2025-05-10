<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit();
}
if (isset($_SESSION['expires_at']) && time() >= $_SESSION['expires_at']) {
    header('Location: ../src/auth/refresh_token.php');
    exit();
}

$access_token = $_SESSION['access_token'];
$user_id = $_SESSION['id'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>Spotify Now Playing</title>
    <link rel="stylesheet" href="css/home.css">
</head>

<body>
    <div class="container">
        <div class="left">
            <div class="now-playing">
                <h2>Now Playing Track</h2>
                <img id="album-image" src="" alt="Album cover">
                <div id="track-name"></div>
                <div id="artist-name"></div>
                <div id="post-count"></div>
            </div>
            <div class="setting">
                <h2>Setting</h2>
                <div class="option">
                    <div class="toggle_switch">
                        <input type="checkbox" id="AutoScrollToggle" class="toggle_input">
                        <label for="AutoScrollToggle" class="toggle_label"></label>
                    </div>
                    <div class="option_text">AutoScroll</div>
                </div>
            </div>
        </div>

        <div class="middle">
            <h2>Track Comments</h2>
            <div class="post-section" id="post-section"></div>
            <div class="post-form">
                <textarea id="post-textarea" placeholder="曲の感想を書こう" required></textarea>
                <input type="hidden" id="track-id-hidden" name="track_id">
                <input type="hidden" id="position-hidden" name="position">
                <input type="hidden" id="track-name-hidden" name="track_name">
                <button id="post-button" type="button">投稿</button>
            </div>
        </div>

        <div class="right">
            <h2>User Information</h2>
            <div class="user-posts-section" id="user-posts-section"></div>
        </div>
    </div>

    <script>
        const accessToken = <?= json_encode($access_token) ?>;
        const user_id = <?= json_encode($user_id) ?>;
        const nowPlayingUrl = 'https://api.spotify.com/v1/me/player/currently-playing';

        let currentTrackId = null;
        let currentPos = null;
        let previousClosestPost = null;
        let autoScroll = false;

        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById("AutoScrollToggle");
            toggle.addEventListener("change", () => {
                autoScroll = toggle.checked;
                if (autoScroll) ScrollToClosestPost(previousClosestPost);
            });

            observePostSection();
            fetchNowPlaying();
            fetchUserPosts(user_id);
            setInterval(fetchNowPlaying, 1000);
        });

        function observePostSection() {
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            node.querySelectorAll('.play-button').forEach(addHoverEffect);
                        }
                    });
                });
            });

            observer.observe(document.getElementById('post-section'), {
                childList: true,
                subtree: true
            });
        }

        function addHoverEffect(button) {
            const img = button.querySelector('.user-button-color-change');
            if (!img) return;
            button.addEventListener('mouseover', () => img.src = './image/HoverPlayButton.png');
            button.addEventListener('mouseout', () => img.src = './image/PlayButton_gray.png');
        }

        async function fetchNowPlaying() {
            try {
                const res = await fetch(nowPlayingUrl, {
                    headers: {
                        'Authorization': 'Bearer ' + accessToken
                    }
                });

                if (res.status === 204 || !res.ok) return resetNowPlayingDisplay();

                const data = await res.json();
                if (!data || !data.item) return resetNowPlayingDisplay();

                const track = data.item;
                const trackId = track.id;
                const trackName = track.name;
                const artistName = track.artists[0]?.name || '';
                const albumImage = track.album.images[0]?.url || '';
                const progressMs = data.progress_ms;

                updateNowPlayingDisplay(trackName, artistName, albumImage, trackId, progressMs);

                if (trackId !== currentTrackId) {
                    currentTrackId = trackId;
                    RemovePosts();
                    fetchPosts(trackId);
                    TrackInfo(trackId);
                }

                currentPos = progressMs;
                findClosestPost(progressMs);

            } catch (e) {
                console.error('Now Playing error:', e);
                resetNowPlayingDisplay();
            }
        }

        function resetNowPlayingDisplay() {
            document.getElementById('track-name').textContent = 'No track currently playing.';
            document.getElementById('artist-name').textContent = '';
            document.getElementById('album-image').src = '';
            ['track-id-hidden', 'position-hidden', 'track-name-hidden'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        }

        function updateNowPlayingDisplay(track, artist, image, id, position) {
            document.getElementById('track-name').textContent = track;
            document.getElementById('artist-name').textContent = artist;
            document.getElementById('album-image').src = image;
            document.getElementById('track-id-hidden').value = id;
            document.getElementById('position-hidden').value = position;
            document.getElementById('track-name-hidden').textContent = track;
        }

        async function fetchPosts(trackId) {
            try {
                const res = await fetch(`get_posts.php?track_id=${trackId}`);
                const posts = await res.json();
                const postSection = document.getElementById('post-section');
                posts.forEach(post => {
                    const postDiv = document.createElement('div');
                    const formattedTime = formatTime(post.position);
                    postDiv.className = 'post';
                    postDiv.setAttribute('positionMs', post.position);
                    postDiv.innerHTML = `
                    <div class="post-left">
                        <div class="position">${formattedTime}</div>
                        <div class="username">${post.name}</div>
                        <div class="content">${post.content}</div>
                    </div>
                    <div class="post-right">
                        <button onclick="seekToPosition(${post.position})" class="play-button">
                            <img src="./image/PlayButton_gray.png" class="user-button-color-change">
                        </button>
                    </div>`;
                    postSection.appendChild(postDiv);
                });
            } catch (e) {
                console.error('Error fetching posts:', e);
            }
        }

        function RemovePosts() {
            const postSection = document.getElementById('post-section');
            while (postSection.firstChild) postSection.removeChild(postSection.firstChild);
        }

        async function TrackInfo(trackId) {
            try {
                const res = await fetch(`get_trackInfo.php?track_id=${trackId}`);
                const count = await res.json();
                document.getElementById('post-count').textContent = count;
            } catch (e) {
                console.error('Track info error:', e);
            }
        }

        async function fetchUserPosts(uid) {
            try {
                const res = await fetch(`get_userPosts.php?user_id=${uid}`);
                const posts = await res.json();
                const section = document.getElementById('user-posts-section');
                if (posts.length === 0) {
                    section.innerHTML = `<div>まだコメントはありません。</div>`;
                    return;
                }
                posts.forEach(post => {
                    const postDiv = document.createElement('div');
                    postDiv.className = 'post';
                    postDiv.innerHTML = `
                    <div class="post-left">
                        <div class="username">${post.name}</div>
                        <div class="post-timestamp">${post.timestamp}</div>
                        <div class="content">${post.content}</div>
                    </div>
                    <div class="post-right">
                        <button onclick="RemovePost(${post.post_id})" class="remove-button">
                            <img src="./image/01120.png" class="color-change">
                        </button>
                    </div>`;
                    section.appendChild(postDiv);
                });
            } catch (e) {
                console.error('User posts fetch failed:', e);
            }
        }

        async function RemovePost(postId) {
            try {
                const res = await fetch('remove_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `post_id=${postId}`
                });
                const result = await res.json();
                if (result.success) window.location.reload();
            } catch (e) {
                console.error('Remove failed:', e);
            }
        }

        function formatTime(ms) {
            const min = Math.floor(ms / 60000);
            const sec = Math.floor((ms % 60000) / 1000);
            return `${min}:${sec.toString().padStart(2, '0')}`;
        }

        function seekToPosition(ms) {
            fetch(`https://api.spotify.com/v1/me/player/seek?position_ms=${ms}`, {
                method: 'PUT',
                headers: {
                    'Authorization': 'Bearer ' + accessToken,
                    'Content-Type': 'application/json'
                }
            }).then(res => {
                if (!res.ok) throw new Error('Seek failed');
                console.log(`Seeked to ${ms}`);
            }).catch(err => console.error(err));
        }

        function findClosestPost(target) {
            const posts = document.getElementsByClassName('post');
            let closest = null;
            let minDiff = Infinity;
            Array.from(posts).forEach(post => {
                const pos = parseInt(post.getAttribute('positionMs'));
                const diff = target - pos;
                if (diff >= 0 && diff < minDiff) {
                    closest = post;
                    minDiff = diff;
                }
            });
            if (closest && closest !== previousClosestPost) {
                if (previousClosestPost) previousClosestPost.style.border = '1px solid transparent';
                closest.style.border = '2px solid var(--spotify-green-color)';
                if (autoScroll) ScrollToClosestPost(closest);
                previousClosestPost = closest;
            }
        }

        function ScrollToClosestPost(post) {
            if (!post) return;
            post.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        document.getElementById('post-button').addEventListener('click', () => {
            const text = document.getElementById('post-textarea').value.trim();
            if (!text) return alert('投稿内容を入力してください');

            const formData = new FormData();
            formData.append('track_id', document.getElementById('track-id-hidden').value);
            formData.append('position', document.getElementById('position-hidden').value);
            formData.append('post', text);
            formData.append('track_name', document.getElementById('track-name-hidden').textContent);

            fetch('post.php', {
                    method: 'POST',
                    body: formData
                }).then(res => res.text())
                .then(() => window.location.reload())
                .catch(e => alert('投稿に失敗しました'));
        });
    </script>
</body>

</html>
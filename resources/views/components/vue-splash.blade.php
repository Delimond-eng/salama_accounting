<div id="vue-splash-loader" class="vue-splash-loader">
    <div class="splash-content">
        <div class="loader-circle"></div>
        <div class="loader-line-mask">
            <div class="loader-line"></div>
        </div>
        <img src="{{ asset('assets/img/icon.png') }}" alt="Salama" class="splash-logo">
    </div>
    <div class="splash-text">
        <h5 class="fw-bold mb-1">SALAMA ACCOUNTING</h5>
        <div class="progress-bar-container">
            <div class="progress-bar-fill"></div>
        </div>
        <p class="text-muted small mt-2">Initialisation du système...</p>
    </div>
</div>

<style>
    .vue-splash-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .splash-content {
        position: relative;
        width: 120px;
        height: 120px;
        margin-bottom: 20px;
    }

    .splash-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 50px;
        height: 50px;
        z-index: 5;
    }

    .loader-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 3px solid #f3f3f3;
        border-radius: 50%;
    }

    .loader-line-mask {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        clip: rect(0, 120px, 120px, 60px);
        animation: rotate 2s infinite linear;
    }

    .loader-line {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        clip: rect(0, 120px, 120px, 60px);
        border: 3px solid #3f7afd;
    }

    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .splash-text {
        text-align: center;
    }

    .splash-text h5 {
        letter-spacing: 3px;
        color: #2c3e50;
    }

    .progress-bar-container {
        width: 150px;
        height: 3px;
        background: #f1f1f1;
        border-radius: 10px;
        margin: 10px auto;
        overflow: hidden;
    }

    .progress-bar-fill {
        width: 30%;
        height: 100%;
        background: #3f7afd;
        animation: progress-ind 2s infinite ease-in-out;
    }

    @keyframes progress-ind {
        0% { margin-left: -30%; }
        100% { margin-left: 100%; }
    }
</style>

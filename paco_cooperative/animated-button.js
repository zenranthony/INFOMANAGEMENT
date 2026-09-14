function applyUIEnhancements() {
  if (!document.getElementById("custom-ui-styles")) {
    const style = document.createElement("style");
    style.id = "custom-ui-styles";
    style.textContent = `
      header nav, .site-header nav, .header-wrap nav, nav { position: relative !important; }
      header nav::after, .site-header nav::after, .header-wrap nav::after, nav::after {
        content: '' !important; position: absolute !important; bottom: -2px !important;
        left: var(--underline-left, 0px) !important; width: var(--underline-width, 0px) !important;
        height: 3px !important; background-color: #e7b84b !important; border-radius: 2px !important;
        transition: left 0.35s cubic-bezier(0.25, 1, 0.5, 1), width 0.35s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.25s ease !important;
        opacity: var(--underline-opacity, 0) !important; pointer-events: none !important;
        box-shadow: 0 2px 8px rgba(231, 184, 75, 0.6) !important;
      }
      header nav a, .site-header nav a, .header-wrap nav a, nav a {
        display: inline-block !important; transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1), color 0.3s ease !important;
        will-change: transform; text-decoration: none !important; border-bottom: none !important;
      }
      header nav a:hover, .site-header nav a:hover, .header-wrap nav a:hover, nav a:hover {
        color: #e7b84b !important; transform: translateY(-3px) scale(1.12) !important; text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
      }
      header nav a::after, .site-header nav a::after, nav a::after { display: none !important; }

      @keyframes buttonPulse {
        0% { box-shadow: 0 0 0 0 rgba(231, 184, 75, 0.6); }
        70% { box-shadow: 0 0 0 10px rgba(231, 184, 75, 0); }
        100% { box-shadow: 0 0 0 0 rgba(231, 184, 75, 0); }
      }

      .animated-button {
        position: relative; display: inline-flex !important; align-items: center; justify-content: center; gap: 4px;
        padding: 12px 22px !important; border: 2px solid #e7b84b !important; font-size: 14px !important;
        background-color: transparent !important; border-radius: 100px !important; font-weight: 600 !important;
        color: #e7b84b !important; box-shadow: 0 0 0 2px rgba(231, 184, 75, 0.4) !important; cursor: pointer; overflow: hidden;
        transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1) !important; text-decoration: none !important;
        box-sizing: border-box; animation: buttonPulse 2.5s infinite; font-family: Georgia, 'Times New Roman', serif !important;
      }
      .animated-button::after {
        content: ''; position: absolute; top: -50%; left: -60%; width: 25%; height: 200%;
        background: rgba(255, 255, 255, 0.4); transform: rotate(30deg); transition: all 0.6s ease; z-index: 2; pointer-events: none;
      }
      .animated-button:hover::after { left: 130%; }
      .animated-button svg {
        position: absolute; width: 16px; height: 16px; fill: #e7b84b; z-index: 9;
        transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1);
      }
      .animated-button .arr-1 { right: 16px; }
      .animated-button .arr-2 { left: -25%; }
      .animated-button .circle {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 20px; height: 20px; background-color: #e7b84b; border-radius: 50%; opacity: 0;
        transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1);
      }
      .animated-button .text {
        position: relative; z-index: 1; transform: translateX(-8px);
        transition: all 0.8s cubic-bezier(0.23, 1, 0.32, 1); color: #e7b84b !important;
      }
      .animated-button:hover {
        animation: none; box-shadow: 0 0 0 12px transparent !important; color: #071d49 !important; border-radius: 12px !important;
      }
      .animated-button:hover .arr-1 { right: -25%; }
      .animated-button:hover .arr-2 { left: 16px; }
      .animated-button:hover .text { transform: translateX(8px); color: #071d49 !important; }
      .animated-button:hover svg { fill: #071d49; }
      .animated-button:active { transform: scale(0.95); box-shadow: 0 0 0 4px #e7b84b !important; }
      .animated-button:hover .circle { width: 220px; height: 220px; opacity: 1; }
    `;
    document.head.appendChild(style);
  }

  const navContainer = document.querySelector("header nav, .site-header nav, .header-wrap nav, nav");
  if (navContainer) {
    const navLinks = Array.from(navContainer.querySelectorAll("a"));
    const updateLinePosition = (targetEl) => {
      if (!targetEl) return;
      const navRect = navContainer.getBoundingClientRect();
      const targetRect = targetEl.getBoundingClientRect();
      navContainer.style.setProperty("--underline-left", `${targetRect.left - navRect.left}px`);
      navContainer.style.setProperty("--underline-width", `${targetRect.width}px`);
      navContainer.style.setProperty("--underline-opacity", "1");
    };
    navLinks.forEach((link) => link.addEventListener("mouseenter", () => updateLinePosition(link)));
    navContainer.addEventListener("mouseleave", () => navContainer.style.setProperty("--underline-opacity", "0"));
  }

  // Broaden selection to include header actions, submit buttons, and general call-to-actions
  const buttonsToTransform = Array.from(document.querySelectorAll("a, button[type='submit'], input[type='submit']"));
  buttonsToTransform.forEach((el) => {
    if (el.classList.contains("animated-button")) return;
    
    const text = el.textContent ? el.textContent.trim().toLowerCase() : (el.value ? el.value.trim().toLowerCase() : "");
    const isTarget = 
      el.classList.contains("header-action") || 
      text.includes("sign up") || 
      text.includes("login") || 
      text.includes("log in") || 
      text.includes("sign in") || 
      text.includes("create account") || 
      text.toLowerCase().includes("back to dashboard");

    if (isTarget) {
      const textContent = el.textContent ? el.textContent.trim() : el.value;
      
      // If it's an input submit element, convert it to a button element for proper inner HTML structure
      if (el.tagName === 'INPUT') {
        const button = document.createElement('button');
        button.type = 'submit';
        button.className = el.className;
        if (el.name) button.name = el.name;
        if (el.id) button.id = el.id;
        button.innerHTML = `
          <div class="circle"></div>
          <span class="text">${textContent}</span>
          <svg class="arr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"></path></svg>
          <svg class="arr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"></path></svg>
        `;
        button.classList.add("animated-button");
        el.replaceWith(button);
      } else {
        el.classList.add("animated-button");
        el.innerHTML = `
          <div class="circle"></div>
          <span class="text">${textContent}</span>
          <svg class="arr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"></path>
          </svg>
          <svg class="arr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z"></path>
          </svg>
        `;
      }
    }
  });
}

applyUIEnhancements();
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", applyUIEnhancements);
}
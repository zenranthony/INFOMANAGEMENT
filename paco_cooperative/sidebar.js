(function () {
  function initPASCCOSidebar() {
    // 1. Check if user is logged in (verify via global header indicators or logout elements)
    const isLoggedOut = !document.querySelector('.pascco-global-account a[href*="logout"], a[href*="logout.php"], .user-name, .pascco-account-name');
    if (isLoggedOut) {
      return; // Exit script entirely if the user is not logged in
    }

    console.log("PASCCO Sidebar: Initializing for logged-in user...");

    // 2. Prevent duplicate initializations
    if (document.getElementById("pascco-sidebar")) return;

    // 3. Inject Boxicons & Fonts
    if (!document.getElementById("boxicons-cdn")) {
      const link = document.createElement("link");
      link.id = "boxicons-cdn";
      link.rel = "stylesheet";
      link.href = "https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css";
      document.head.appendChild(link);
    }

    if (!document.getElementById("sidebar-font")) {
      const linkFont = document.createElement("link");
      linkFont.id = "sidebar-font";
      linkFont.rel = "stylesheet";
      linkFont.href = "https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap";
      document.head.appendChild(linkFont);
    }

    // 4. Inject CSS Styles with high priority
    if (!document.getElementById("pascco-sidebar-styles")) {
      const style = document.createElement("style");
      style.id = "pascco-sidebar-styles";
      style.textContent = `
        :root {
          --pascco-blue: #092e5c;
          --pascco-gold: #f4d35e;
          --sidebar-panel: #ffffff;
          --sidebar-text: #333333;
        }

        /* Hamburger Icon */
        .pascco-hamburger-btn {
          font-size: 32px !important;
          color: #ffffff !important;
          cursor: pointer !important;
          display: inline-flex !important;
          align-items: center !important;
          justify-content: center !important;
          vertical-align: middle !important;
          margin: 0 15px !important;
          padding: 5px !important;
          z-index: 999999 !important;
          transition: transform 0.2s ease, color 0.2s ease !important;
        }

        .pascco-hamburger-btn:hover {
          color: var(--pascco-gold) !important;
          transform: scale(1.1) !important;
        }

        /* Overlay Backdrop */
        #pascco-sidebar-overlay {
          position: fixed !important;
          top: 0 !important;
          left: 0 !important;
          width: 100vw !important;
          height: 100vh !important;
          background: rgba(0, 0, 0, 0.5) !important;
          z-index: 999998 !important;
          opacity: 0 !important;
          visibility: hidden !important;
          transition: opacity 0.3s ease, visibility 0.3s ease !important;
        }

        #pascco-sidebar-overlay.active {
          opacity: 1 !important;
          visibility: visible !important;
        }

        /* Slide-in Sidebar */
        #pascco-sidebar {
          position: fixed !important;
          top: 0 !important;
          left: -320px !important;
          height: 100vh !important;
          width: 280px !important;
          padding: 20px 15px !important;
          background: var(--sidebar-panel) !important;
          transition: left 0.35s cubic-bezier(0.25, 1, 0.5, 1) !important;
          box-shadow: 5px 0 25px rgba(0, 0, 0, 0.3) !important;
          z-index: 999999 !important;
          font-family: 'Poppins', sans-serif !important;
          box-sizing: border-box !important;
        }

        #pascco-sidebar.open {
          left: 0 !important;
        }

        #pascco-sidebar header {
          display: flex !important;
          align-items: center !important;
          justify-content: space-between !important;
          padding-bottom: 15px !important;
          border-bottom: 2px solid #f0f0f0 !important;
        }

        #pascco-sidebar header .user-info-container {
          display: flex !important;
          flex-direction: column !important;
        }

        #pascco-sidebar header .logo-text {
          font-weight: 700 !important;
          font-size: 18px !important;
          color: var(--pascco-blue) !important;
          line-height: 1.2 !important;
        }

        #pascco-sidebar header .profile-subtext {
          font-size: 12px !important;
          color: #707070 !important;
          font-weight: 500 !important;
          margin-top: 3px !important;
          display: flex !important;
          align-items: center !important;
          gap: 4px !important;
          text-decoration: none !important;
        }

        #pascco-sidebar header .profile-subtext:hover {
          color: var(--pascco-blue) !important;
        }

        #pascco-sidebar header .close-btn {
          font-size: 28px !important;
          color: var(--sidebar-text) !important;
          cursor: pointer !important;
        }

        #pascco-sidebar .menu-links {
          list-style: none !important;
          padding: 0 !important;
          margin: 20px 0 0 0 !important;
        }

        #pascco-sidebar .menu-links li {
          margin-bottom: 8px !important;
        }

        #pascco-sidebar .menu-links a {
          display: flex !important;
          align-items: center !important;
          padding: 10px 14px !important;
          color: var(--sidebar-text) !important;
          text-decoration: none !important;
          border-radius: 8px !important;
          font-size: 14px !important;
          font-weight: 500 !important;
          transition: background 0.2s ease, color 0.2s ease !important;
        }

        #pascco-sidebar .menu-links li.active a,
        #pascco-sidebar .menu-links a:hover {
          background-color: var(--pascco-blue) !important;
          color: var(--pascco-gold) !important;
        }

        #pascco-sidebar .menu-links a i {
          font-size: 22px !important;
          margin-right: 12px !important;
        }
      `;
      document.head.appendChild(style);
    }

    // 5. Extract User's Name dynamically from header DOM
    let userName = "PASCCO Member";
    const headerNodes = document.querySelectorAll("header, .navbar, .site-header, nav");
    
    headerNodes.forEach(header => {
      const textNodes = Array.from(header.querySelectorAll("span, div, p, a"));
      const nameNode = textNodes.find(el => el.textContent.includes("maria_santos") || el.textContent.includes("_") || el.classList.contains("user-name") || el.classList.contains("pascco-account-name"));
      if (nameNode && nameNode.textContent.trim()) {
        userName = nameNode.textContent.trim().replace("Logout", "").trim();
      }
    });

    // 6. Create Overlay & Sidebar DOM elements
    const overlay = document.createElement("div");
    overlay.id = "pascco-sidebar-overlay";

    const sidebar = document.createElement("nav");
    sidebar.id = "pascco-sidebar";
    sidebar.innerHTML = `
      <header>
        <div class="user-info-container">
          <span class="logo-text"></span>
          <a href="profile.php" class="profile-subtext"><i class='bx bx-user-circle'></i> My Profile</a>
        </div>
        <i class='bx bx-x close-btn' id="close-pascco-sidebar"></i>
      </header>
      <ul class="menu-links">
        <li><a href="index.php"><i class='bx bx-home-alt'></i><span>Home</span></a></li>
        <li><a href="dashboard.php"><i class='bx bx-user-pin'></i><span>Member's Dashboard</span></a></li>
        <li><a href="member_savings.php"><i class='bx bx-wallet'></i><span>My Savings</span></a></li>
        <li><a href="open_account.php"><i class='bx bx-folder-plus'></i><span>Open Account</span></a></li>
        <li><a href="deposit.php"><i class='bx bx-plus-circle'></i><span>Deposit Money</span></a></li>
        <li><a href="announcements.php"><i class='bx bx-group'></i><span>Community</span></a></li>
        <li><a href="transfer.php"><i class='bx bx-transfer-alt'></i><span>Transfer Funds</span></a></li>
        <li><a href="apply_loan.php"><i class='bx bx-credit-card'></i><span>Apply for Loan</span></a></li>
      </ul>
    `;

    sidebar.querySelector(".logo-text").textContent = userName;
    document.body.appendChild(overlay);
    document.body.appendChild(sidebar);

    // Stop propagation so clicking inside sidebar won't close it
    sidebar.addEventListener("click", function (e) {
      e.stopPropagation();
    });

    // 7. Detect current URL and automatically set active menu link
    const currentPath = window.location.pathname.split("/").pop();
    const menuItems = sidebar.querySelectorAll(".menu-links li");

    menuItems.forEach((li) => {
      const link = li.querySelector("a");
      const href = link.getAttribute("href");

      li.classList.remove("active");
      if (href === currentPath || (currentPath === "" && href === "dashboard.php")) {
        li.classList.add("active");
      }
    });

    // 8. Create Hamburger Button inside existing page Header
    const hamburgerBtn = document.createElement("i");
    hamburgerBtn.className = "bx bx-menu pascco-hamburger-btn";
    hamburgerBtn.id = "open-pascco-sidebar";

    const headerEl = document.querySelector(".pascco-global-header, .navbar, header, .header, .site-header, .header-wrap, nav");
    if (headerEl) {
      headerEl.insertBefore(hamburgerBtn, headerEl.firstChild);
    } else {
      hamburgerBtn.style.position = "fixed";
      hamburgerBtn.style.top = "12px";
      hamburgerBtn.style.left = "12px";
      hamburgerBtn.style.background = "#092e5c";
      hamburgerBtn.style.borderRadius = "6px";
      document.body.appendChild(hamburgerBtn);
    }

    // 9. Robust Toggle Functions
    function openSidebar(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
      }
      sidebar.classList.add("open");
      overlay.classList.add("active");
    }

    function closeSidebar(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      sidebar.classList.remove("open");
      overlay.classList.remove("active");
    }

    // Attach Click Listeners
    hamburgerBtn.addEventListener("click", openSidebar);
    document.getElementById("close-pascco-sidebar").addEventListener("click", closeSidebar);
    overlay.addEventListener("click", closeSidebar);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPASCCOSidebar);
  } else {
    initPASCCOSidebar();
  }
})();

<?php
$hideFab = isset($pageTitle) && in_array($pageTitle, ['Book a Ride', 'Profile']);
if (!$hideFab):
?>
<style>
  .fab-book-ride {
    position: fixed;
    bottom: 72px;
    right: 32px;
    width: 100px;
    height: 100px;
    z-index: 1050;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .fab-book-ride .fab-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    animation: spinText 10s linear infinite;
  }
  .fab-book-ride .fab-ring svg { width: 100%; height: 100%; }
  .fab-book-ride .fab-ring text {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
    fill: #fff;
  }
  .fab-book-ride .fab-ring-bg {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(243, 122, 32, .92);
    backdrop-filter: blur(4px);
  }

  .fab-book-ride .fab-core {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #fff;
    box-shadow:
      0 2px 12px rgba(0, 0, 0, .12),
      0 0 0 2px rgba(243, 122, 32, .15);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .fab-book-ride .fab-core i {
    font-size: 1.15rem;
    color: #f37a20;
  }

  @keyframes spinText { to { transform: rotate(360deg); } }
</style>

<a href="bookRide.php" class="fab-book-ride" title="Book a Ride">
  <div class="fab-ring-bg"></div>
  <div class="fab-ring">
    <svg viewBox="0 0 100 100">
      <defs>
        <path id="fabCirclePath" d="M 50,50 m -35,0 a 35,35 0 1,1 70,0 a 35,35 0 1,1 -70,0"/>
      </defs>
      <text>
        <textPath href="#fabCirclePath" startOffset="0%">BOOK RIDE &#x2022; </textPath>
      </text>
      <text>
        <textPath href="#fabCirclePath" startOffset="50%">BOOK RIDE &#x2022; </textPath>
      </text>
    </svg>
  </div>
  <div class="fab-core">
    <i class="bi bi-car-front-fill"></i>
  </div>
</a>
<?php endif; ?>

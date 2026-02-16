<div class="d-flex flex-column h-100">
        <div
          class="sidebar-header mb-10 d-flex justify-content-center align-items-center"
          style="height: 60px"
        >
          <img
            src="assets/powercabs-logo.svg"
            alt="Navigation Logo"
            class="img-fluid"
            style="max-height: 75px"
          />
        </div>

        <ul class="nav nav-pills flex-column mt-4">
          <li class="nav-item mb-3">
            <a
              href="home.php"
              class="nav-link fw-normal text-white"
              style="font-size: 18px"
            >
              <i
                class="bi bi-house-door-fill me-2"
                style="font-size: 1.2rem"
              ></i>
              Dashboard
            </a>
          </li>
          <li class="nav-item mb-3 fw-normal">
            <a
              href="employee.php"
              class="nav-link text-white"
              style="font-size: 18px"
            >
              <i class="bi bi-people-fill me-2" style="font-size: 1.2rem"></i>
              Employees
            </a>
          </li>
          <li class="nav-item mb-3 fw-normal">
            <a
              href="rideHistory.php"
              class="nav-link text-white"
              style="font-size: 18px"
            >
              <i class="bi bi-clock-history me-2" style="font-size: 1.2rem"></i>
              Rides History
            </a>
          </li>
          <!-- <li class="nav-item mb-3 fw-normal">
            <a
              href="promotion.php"
              class="nav-link text-white"
              style="font-size: 18px"
            >
              <i
                class="bi bi-ticket-perforated me-2"
                style="font-size: 1.2rem"
              ></i>
              Promotions
            </a>
          </li> -->
        </ul>

        <div class="mt-auto pt-3">
          <div class="nav-item dropdown">
            <a
              href="#"
              class="nav-link text-white d-flex justify-content-between align-items-center"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <?= $user['name']; ?>
              <i class="bi bi-chevron-down rotate-icon ms-2"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li>
                <a class="dropdown-item" href="profile.php"
                  ><i class="bi bi-person me-2"></i>Profile</a
                >
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li>
                <a class="dropdown-item" href="index.php"
                  ><i class="bi bi-box-arrow-right me-2"></i>Logout</a
                >
              </li>
            </ul>
          </div>
        </div>
      </div>
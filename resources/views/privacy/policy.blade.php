<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Privacy Policy – EMS | SoloChoicez</title>
  <meta name="description" content="Privacy Policy for the EMS employee management mobile application by SoloChoicez." />
  <style>
    :root {
      --text: #1a1a1a;
      --muted: #555;
      --border: #e5e5e5;
      --bg: #f7f8fa;
      --card: #ffffff;
      --accent: #0b5fff;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Georgia, "Times New Roman", serif;
      color: var(--text);
      background: var(--bg);
      line-height: 1.7;
    }
    .wrap {
      max-width: 820px;
      margin: 0 auto;
      padding: 40px 20px 80px;
    }
    header {
      background: var(--card);
      border: 1px solid var(--border);
      padding: 32px 28px;
      margin-bottom: 28px;
    }
    header h1 {
      margin: 0 0 8px;
      font-size: 2rem;
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    header .meta {
      color: var(--muted);
      font-size: 0.95rem;
      font-family: system-ui, sans-serif;
    }
    article {
      background: var(--card);
      border: 1px solid var(--border);
      padding: 36px 28px;
    }
    h2 {
      margin: 2rem 0 0.75rem;
      font-size: 1.25rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 0.4rem;
    }
    h2:first-child { margin-top: 0; }
    h3 {
      margin: 1.25rem 0 0.5rem;
      font-size: 1.05rem;
    }
    p, li { font-size: 1rem; }
    ul { padding-left: 1.25rem; }
    li { margin-bottom: 0.35rem; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 1rem 0 1.25rem;
      font-family: system-ui, sans-serif;
      font-size: 0.92rem;
    }
    th, td {
      border: 1px solid var(--border);
      padding: 10px 12px;
      text-align: left;
      vertical-align: top;
    }
    th {
      background: #f0f2f5;
      font-weight: 600;
    }
    a { color: var(--accent); }
    .note {
      background: #f0f2f5;
      border-left: 3px solid var(--accent);
      padding: 12px 14px;
      margin: 1rem 0;
      font-family: system-ui, sans-serif;
      font-size: 0.95rem;
    }
    footer {
      margin-top: 24px;
      color: var(--muted);
      font-size: 0.9rem;
      font-family: system-ui, sans-serif;
      text-align: center;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 16px;
      font-family: system-ui, sans-serif;
      font-size: 0.9rem;
      text-decoration: none;
    }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="wrap">
    <a class="back-link" href="{{ route('login') }}">&larr; Back to Login</a>
    <header>
      <h1>Privacy Policy</h1>
      <p class="meta">
        <strong>App:</strong> EMS<br />
        <strong>Organization:</strong> SoloChoicez<br />
        <strong>Last updated:</strong> July 30, 2026
      </p>
    </header>

    <article>
      <p>
        <strong>EMS</strong> (“App”, “we”, “us”, or “our”) is operated by <strong>SoloChoicez</strong>.
        This Privacy Policy explains how we collect, use, store, share, and protect information when you
        use the EMS mobile application available on Google Play and related services.
      </p>
      <p>
        By downloading, installing, or using the App, you agree to this Privacy Policy.
        If you do not agree, please do not use the App.
      </p>

      <h2>1. Who this App is for</h2>
      <p>
        EMS is an employee management application for authorized employees and staff of SoloChoicez
        (and organizations using this EMS system). It is a workplace tool for login, attendance,
        profile management, leave/work-from-home requests, and work-related notifications.
      </p>
      <p>
        The App is <strong>not</strong> directed at children under 13, and we do not knowingly collect
        personal information from children.
      </p>

      <h2>2. Information we collect</h2>
      <p>Depending on how you use the App, we may collect the following categories of information:</p>

      <h3>2.1 Account and profile information</h3>
      <ul>
        <li>Username and password (used to sign you in; password is not stored on the device after login)</li>
        <li>Full name, email address, and contact number</li>
        <li>Date of birth, gender, and marital status</li>
        <li>Address, city, state, and zip/postal code</li>
        <li>Employee/staff ID, department, designation, company, role, office shift, joining/exit dates, and attendance type</li>
        <li>Profile photograph (if you upload one)</li>
      </ul>

      <h3>2.2 Attendance and location data</h3>
      <ul>
        <li>Clock-in and clock-out times and related attendance records</li>
        <li>
          Device location (approximate and/or precise latitude and longitude) when you clock in or clock out,
          <strong>only if</strong> your attendance method requires GPS (location-based attendance)
        </li>
        <li>Location is collected while using the App (foreground). We do <strong>not</strong> continuously track your location in the background</li>
        <li>Employees configured for IP-based attendance may not need GPS for punching</li>
      </ul>

      <h3>2.3 Leave and work-from-home information</h3>
      <ul>
        <li>Leave type, start and end dates, reasons, and remarks</li>
        <li>Work-from-home request details (dates, reason, remarks, and related fields)</li>
      </ul>

      <h3>2.4 Device, session, and notification data</h3>
      <ul>
        <li>Authentication/session token after successful login</li>
        <li>Cached user profile information on the device for App use</li>
        <li>
          <strong>Push notification device token (FCM token)</strong> so we can send you work-related
          push notifications
        </li>
        <li>Basic technical information needed to operate notifications and App connectivity</li>
      </ul>

      <h3>2.5 Photos and media</h3>
      <ul>
        <li>Images you capture with the camera or select from your gallery when updating your profile photo</li>
      </ul>

      <h3>2.6 Information stored locally on your device</h3>
      <ul>
        <li>Login session token</li>
        <li>Cached user profile data</li>
        <li>Push notification (FCM) token</li>
        <li>Permission onboarding completion flag</li>
      </ul>
      <p class="note">
        We do <strong>not</strong> store your password in local device storage after login.
      </p>

      <h2>3. App permissions we request</h2>
      <p>
        To provide core features, EMS may request the following device permissions.
        Permissions are requested only when needed, and you can allow or deny them.
      </p>
      <table>
        <thead>
          <tr>
            <th>Permission</th>
            <th>When it may be requested</th>
            <th>Purpose</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Internet / Network access</strong></td>
            <td>Required for App operation</td>
            <td>Connect to EMS servers for login, attendance, profile, leave/WFH, and notifications</td>
          </tr>
          <tr>
            <td><strong>Location (approximate &amp; precise) — while using the App</strong></td>
            <td>During first-launch permission onboarding, and again when clocking in/out if GPS attendance is enabled</td>
            <td>Location-based attendance verification for clock-in and clock-out. Not used for continuous background tracking</td>
          </tr>
          <tr>
            <td><strong>Camera</strong></td>
            <td>Only when you choose to take a profile photo</td>
            <td>Capture your profile photograph</td>
          </tr>
          <tr>
            <td><strong>Photos / media (read images)</strong></td>
            <td>Only when you choose a profile photo from gallery</td>
            <td>Select an existing image as your profile photograph</td>
          </tr>
          <tr>
            <td><strong>Notifications / Push notifications</strong></td>
            <td>After login, or when the device asks for notification permission</td>
            <td>
              Allow EMS to send <strong>push notifications</strong> for work/HR updates such as leave status,
              announcements, and other important alerts using Firebase Cloud Messaging (FCM), and to display
              notifications while the App is in use
            </td>
          </tr>
        </tbody>
      </table>
      <p>
        If you deny or later revoke a permission in your device settings, related features may be limited
        (for example: GPS attendance without location permission, profile photo upload without camera/gallery
        permission, or no push alerts without notification permission).
      </p>

      <h2>4. How we use your information</h2>
      <p>We use collected information to:</p>
      <ul>
        <li>Authenticate users and provide secure access to EMS features</li>
        <li>Record and manage attendance, including location verification where required by company policy</li>
        <li>Display and update employee profile information</li>
        <li>Process leave and work-from-home requests</li>
        <li>Send push notifications and show in-app notifications related to work/HR matters</li>
        <li>Maintain security, prevent misuse, troubleshoot issues, and improve App reliability</li>
        <li>Meet legal, compliance, and internal company obligations</li>
      </ul>
      <p><strong>We do not sell your personal information.</strong></p>
      <p><strong>We do not use your personal information for third-party advertising.</strong></p>

      <h2>5. How we share information</h2>
      <p>Your information may be processed by or shared with:</p>
      <table>
        <thead>
          <tr>
            <th>Recipient</th>
            <th>Purpose</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>SoloChoicez / EMS servers</strong> (including services hosted at ems.solochoicez.cloud)</td>
            <td>Core HR operations: authentication, attendance, profile, leave/WFH, and notifications</td>
          </tr>
          <tr>
            <td><strong>Authorized company administrators / HR staff</strong></td>
            <td>Managing employees, attendance, leaves, and related workplace processes</td>
          </tr>
          <tr>
            <td><strong>Google Firebase</strong> (Firebase Cloud Messaging; Firebase Analytics may be included in the App build)</td>
            <td>Delivering push notifications and supporting app analytics/operational metrics</td>
          </tr>
          <tr>
            <td><strong>Google Play services</strong></td>
            <td>App distribution, notifications, and related device platform services</td>
          </tr>
        </tbody>
      </table>
      <p>
        We may also disclose information if required by law, regulation, legal process, or to protect the
        rights, safety, and security of SoloChoicez, users, or others.
      </p>

      <h2>6. Push notifications</h2>
      <p>
        With your permission, EMS requests <strong>notification permission</strong> and registers a
        <strong>device push token (FCM token)</strong> with our servers. This allows us to send
        work-related push notifications, such as:
      </p>
      <ul>
        <li>Leave or work-from-home updates</li>
        <li>HR announcements and alerts</li>
        <li>Other important workplace notifications</li>
      </ul>
      <p>
        You can turn off notifications at any time in your device settings. When you log out, we
        remove or invalidate the device token where supported by the system.
      </p>

      <h2>7. Data retention</h2>
      <ul>
        <li>
          Account, profile, attendance, leave, and related HR records are retained on company systems
          as needed for employment, payroll, compliance, and business operations
        </li>
        <li>
          Session tokens and locally cached data remain on your device until you log out or clear App data
        </li>
        <li>
          Push tokens are updated while you use the App and are removed/invalidated on logout where supported
        </li>
      </ul>

      <h2>8. Data security</h2>
      <p>
        We use reasonable technical and organizational measures to protect your information, including
        authenticated access to our APIs. However, no method of transmission over the internet or
        electronic storage is 100% secure. Please keep your login credentials confidential and do not
        share your account with others.
      </p>

      <h2>9. Your choices and rights</h2>
      <p>Subject to applicable law and company policy, you may:</p>
      <ul>
        <li>Access or update certain profile information in the App</li>
        <li>Allow, deny, or revoke location, camera, photos, or notification permissions in device settings</li>
        <li>Log out to clear the local session from the App</li>
        <li>
          Contact us to request access, correction, or deletion of personal data where legally allowed
          and compatible with employment/HR record requirements
        </li>
      </ul>

      <h2>10. International and cloud processing</h2>
      <p>
        Data may be processed on servers and cloud services used by SoloChoicez to operate EMS,
        which may include our hosting infrastructure and Google Firebase / Google Play services.
      </p>

      <h2>11. Third-party services</h2>
      <p>
        The App uses third-party services that may process certain device or usage data according to
        their own policies, including:
      </p>
      <ul>
        <li>Google Firebase (Cloud Messaging / Analytics components)</li>
        <li>Google Play services</li>
      </ul>
      <p>
        We encourage you to review the privacy practices of those providers for more information about
        their processing.
      </p>

      <h2>12. Children’s privacy</h2>
      <p>
        EMS is not intended for children under 13. We do not knowingly collect personal information
        from children. If you believe a child has provided us personal information, contact us and we
        will take appropriate steps.
      </p>

      <h2>13. Changes to this Privacy Policy</h2>
      <p>
        We may update this Privacy Policy from time to time. When we do, we will revise the
        “Last updated” date at the top of this page. Continued use of the App after changes means
        you accept the updated Privacy Policy.
      </p>

      <h2>14. Contact us</h2>
      <p>For privacy questions, permission concerns, or data requests, contact:</p>
      <ul>
        <li><strong>Email:</strong> <a href="mailto:it_support@solochoicez.com">it_support@solochoicez.com</a></li>
        <li><strong>App:</strong> EMS</li>
        <li><strong>Organization:</strong> SoloChoicez</li>
      </ul>

      <h2>15. Summary for app store transparency</h2>
      <p>In plain terms, EMS may collect and use:</p>
      <ul>
        <li>Account and profile details for employee access and HR features</li>
        <li>Location (while using the App) for GPS-based attendance</li>
        <li>Camera and photo access for profile pictures</li>
        <li>Push notification permission and device push token for work alerts</li>
        <li>Attendance, leave, and WFH records for workplace operations</li>
      </ul>
      <p>
        Data is used for App functionality and workplace management, is <strong>not sold</strong>,
        and is <strong>not used for third-party advertising</strong>.
      </p>
    </article>

    <footer>
      © {{ date('Y') }} SoloChoicez · EMS Privacy Policy
    </footer>
  </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

  <head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <!--[if mso]>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    <style>
      td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
    </style>
  <![endif]-->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700" rel="stylesheet" media="screen">
    <style>
      .hover-underline:hover {
        text-decoration: underline !important;
      }

      @keyframes spin {
        to {
          transform: rotate(360deg);
        }
      }

      @keyframes ping {

        75%,
        100% {
          transform: scale(2);
          opacity: 0;
        }
      }

      @keyframes pulse {
        50% {
          opacity: .5;
        }
      }

      @keyframes bounce {

        0%,
        100% {
          transform: translateY(-25%);
          animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
        }

        50% {
          transform: none;
          animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
        }
      }

      @media (max-width: 600px) {
        .sm-px-24 {
          padding-left: 24px !important;
          padding-right: 24px !important;
        }

        .sm-py-32 {
          padding-top: 32px !important;
          padding-bottom: 32px !important;
        }

        .sm-w-full {
          width: 100% !important;
        }
      }
    </style>
  </head>

  <body style="margin: 0; padding: 0; width: 100%; word-break: break-word; -webkit-font-smoothing: antialiased;">
    <div role="article" aria-roledescription="email" aria-label="" lang="en">
      <table style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
          <td align="center" style="--bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity)); font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;" bgcolor="rgba(236, 239, 241, var(--bg-opacity))">
            <table class="sm-w-full" style="font-family: 'Montserrat',Arial,sans-serif; width: 600px;" width="600" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="sm-py-32 sm-px-24" style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; padding: 48px; text-align: center;" align="center">
                  <a href="https://it-vision-studio.de/Kanzlei">
                    <img src="{{asset('images/logo/logo-invoice.png')}}" width="100" width="50" alt="Rechnung" style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;">
                  </a>
                </td>
              </tr>
              <tr>
                <td align="center" class="sm-px-24" style="font-family: 'Montserrat',Arial,sans-serif;">
                  <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                      <td class="sm-px-24" style="--bg-opacity: 1; background-color: #ffffff; background-color: rgba(255, 255, 255, var(--bg-opacity)); border-radius: 4px; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 14px; line-height: 24px; padding: 48px; text-align: left; --text-opacity: 1; color: #626262; color: rgba(98, 98, 98, var(--text-opacity));" bgcolor="rgba(255, 255, 255, var(--bg-opacity))" align="left">
                        <p style="font-weight: 600; font-size: 18px; margin-bottom: 0;">Sehr geehrte(r)</p>
                        <p style="font-weight: 700; font-size: 20px; margin-top: 0; --text-opacity: 1; color: #ff5850; color: rgba(255, 88, 80, var(--text-opacity));">{{ $invoice->user->name }},</p>
                        <p style="margin: 0 0 24px;">
                          anbei erhalten Sie Ihre Rechnung Nr.#{{ $invoice->invoice_no }} von {{ Carbon\Carbon::parse($invoice->invoice_date)->format('d.m.Y') }} im pdf-Format.
                        </p>
                        <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                          <tr>
                            <td style="font-family: 'Montserrat',Arial,sans-serif; font-size: 14px; padding: 16px;">
                              <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                  <td style="font-family: 'Montserrat',Arial,sans-serif; font-size: 14px;"><strong>Gesamtbetrag:</strong>€ {{ $invoice->total_price }}</td>
                                </tr>
                                <tr>
                                  <td style="font-family: 'Montserrat',Arial,sans-serif; font-size: 14px;">
                                    <strong>Fälligkeitsdatum:</strong> {{ Carbon\Carbon::parse($invoice->invoice_due_date)->format('d.m.Y') }}
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                        <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                          <tr>
                            <td style="font-family: 'Montserrat',Arial,sans-serif;">
                              <h3 style="font-weight: 700; font-size: 12px; margin-top: 0; text-align: left;">#{{ $invoice->invoice_no }}</h3>
                            </td>
                            <td style="font-family: 'Montserrat',Arial,sans-serif;">
                              <h3 style="font-weight: 700; font-size: 12px; margin-top: 0; text-align: right;">
                                {{ Carbon\Carbon::parse($invoice->invoice_date)->format('d.m.Y') }}
                              </h3>
                            </td>
                          </tr>
                          <tr>
                            <td colspan="2" style="font-family: 'Montserrat',Arial,sans-serif;">
                              <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                  <th align="left" style="padding-bottom: 8px;">
                                    <p>Beschreibung</p>
                                  </th>
                                  <th align="right" style="padding-bottom: 8px;">
                                    <p>Betrag</p>
                                  </th>
                                </tr>

	                              @foreach ($invoice->items as $item)
                                <tr>
                                  <td style="font-family: 'Montserrat',Arial,sans-serif; font-size: 14px; padding-top: 10px; padding-bottom: 10px; width: 80%;" width="80%">
                                    {{ $item->item_detail }}
                                  </td>
                                  <td align="right" style="font-family: 'Montserrat',Arial,sans-serif; font-size: 14px; text-align: right; width: 20%;" width="20%">€ {{ $item->price }}</td>
                                </tr>
	                              @endforeach

                                <tr>
                                  <td style="font-family: 'Montserrat',Arial,sans-serif; width: 80%;" width="80%">
                                    <p align="right" style="font-weight: 700; font-size: 14px; line-height: 24px; margin: 0; padding-right: 16px; text-align: right;">
                                      Gesamtbetrag
                                    </p>
                                  </td>
                                  <td style="font-family: 'Montserrat',Arial,sans-serif; width: 20%;" width="20%">
                                    <p align="right" style="font-weight: 700; font-size: 14px; line-height: 24px; margin: 0; text-align: right;">
                                      € {{ $invoice->total_price }}
                                    </p>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                        <table align="center" style="font-family: 'Montserrat',Arial,sans-serif; margin-left: auto; margin-right: auto; text-align: center; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                          <tr>
                            <td align="right" style="font-family: 'Montserrat',Arial,sans-serif;">
                              <table style="font-family: 'Montserrat',Arial,sans-serif; margin-top: 24px; margin-bottom: 24px;" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                  <td align="right" style="mso-padding-alt: 16px 24px; --bg-opacity: 1; background-color: #7367f0; background-color: rgba(115, 103, 240, var(--bg-opacity)); border-radius: 4px; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;" bgcolor="rgba(115, 103, 240, var(--bg-opacity))">

                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                        <p style="font-size: 14px; line-height: 24px; margin-top: 6px; margin-bottom: 20px;">
                          Für Rückfragen stehen wir Ihnen gerne zur Verfügung.
                        </p>
                        <p style="font-size: 14px; line-height: 24px; margin-top: 6px; margin-bottom: 20px;">
                         Mit freundlichen Grüßen
                          <br>David Melendez
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="font-family: 'Montserrat',Arial,sans-serif; height: 20px;" height="20"></td>
              </tr>
              <tr>
                <td style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 12px; padding-left: 48px; padding-right: 48px; --text-opacity: 1; color: #eceff1; color: rgba(236, 239, 241, var(--text-opacity));">
                  <p align="center" style="cursor: default; margin-bottom: 16px;">
                    <a href="#" style="--text-opacity: 1; color: #263238; color: rgba(38, 50, 56, var(--text-opacity)); text-decoration: none;"><img src="images/facebook.png" width="17" alt="Facebook" style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle; margin-right: 12px;"></a>
                    &bull;
                    <a href="#" style="--text-opacity: 1; color: #263238; color: rgba(38, 50, 56, var(--text-opacity)); text-decoration: none;"><img src="images/twitter.png" width="17" alt="Twitter" style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle; margin-right: 12px;"></a>
                    &bull;
                    <a href="#" style="--text-opacity: 1; color: #263238; color: rgba(38, 50, 56, var(--text-opacity)); text-decoration: none;"><img src="images/instagram.png" width="17" alt="Instagram" style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle; margin-right: 12px;"></a>
                  </p>
                  <p style="--text-opacity: 1; color: #263238; color: rgba(38, 50, 56, var(--text-opacity));">
                    Unsere Datenschutzerklärung finden Sie hier
                    <a href="#" class="hover-underline" style="--text-opacity: 1; color: #7367f0; color: rgba(115, 103, 240, var(--text-opacity)); text-decoration: none;">Datenschutz</a> |
                    <a href="#" class="hover-underline" style="--text-opacity: 1; color: #7367f0; color: rgba(115, 103, 240, var(--text-opacity)); text-decoration: none;">Impressum</a>.
                  </p>
                </td>
              </tr>
              <tr>
                <td style="font-family: 'Montserrat',Arial,sans-serif; height: 16px;" height="16"></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </div>
  </body>
</html>

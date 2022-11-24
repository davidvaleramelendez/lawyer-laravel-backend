<html>
    <head>
    
    </head>
    <body>
        <table style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tbody><tr>
              <td align="center" style="--bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity)); font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;" bgcolor="rgba(236, 239, 241, var(--bg-opacity))">
                <table style="font-family: 'Montserrat',Arial,sans-serif; width: 600px;" width="600" cellpadding="0" cellspacing="0" role="presentation">
                  <tbody>
                    <tr>
                      <td style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; padding: 48px; text-align: center;" align="center">
                        <a target="_blank" href="{{env('USE_APP_URL')}}" style="text-decoration: none; color: #7367f0">
                          <div style="justify-content: center; display: flex; ">
                              <img src="{{asset('images/icons/logo.svg')}}" width="50" alt="Vuexy Admin" style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;">
                              <h2 style="margin-left: 10px; color:#7367f0">Lawyer</h2>
                          </div> 
                        </a>
                      </td>
                    </tr>
                  <tr>
                    <td align="center"style="font-family: 'Montserrat',Arial,sans-serif;">
                      <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody><tr>
                          <td style="--bg-opacity: 1; background-color: #ffffff; background-color: rgba(255, 255, 255, var(--bg-opacity)); border-radius: 4px; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 14px; line-height: 24px; padding: 48px; text-align: left; --text-opacity: 1; color: #626262; color: rgba(98, 98, 98, var(--text-opacity));" bgcolor="rgba(255, 255, 255, var(--bg-opacity))" align="left">
                            {!! $template->template !!}
                            <p style="font-size: 14px; line-height: 24px; margin-top: 6px; margin-bottom: 20px;">
                              Cheers,
                              <br>The Lawyer Team
                            </p>
                          </td>
                          
                        </tr>
                        <tr style="background:#FFFFFF; margin-bottom: 3.5rem">
                          <td style="padding-left: 1.5rem">
                            <div style="margin-bottom: 1.5rem;">
                              <div style="display: flex; flex-wrap: wrap; justify-content: flex-start; align-items: center;">
                                @foreach($template->EmailTemplateAttachment as $key => $value)
                                <a target="_blank" href="{{asset($value->path)}}" style="background-color: #7367f0; display: inline-block; font-size: 85%; font-weight: 600; line-height: 1; color: #fff; white-space: nowrap; vertical-align: baseline; padding: 0.3rem 0.5rem; text-align: center; border-radius: 0.358rem;"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="cursor: pointer; margin-right: 1rem"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>{{$value->file_name}}</a>
                                @endforeach
                              </div>
                            </div>
                          </td>
                        </tr>
                      </tbody></table>
                    </td>
                  </tr>
                  
                  <tr>
                    <td style="font-family: 'Montserrat',Arial,sans-serif; height: 20px;" height="20"></td>
                  </tr>
                  <tr>
                    <td style="font-family: 'Montserrat',Arial,sans-serif; height: 16px;" height="16"></td>
                  </tr>
                </tbody></table>
              </td>
            </tr>
          </tbody>
        </table>
        
     </body>
</html>
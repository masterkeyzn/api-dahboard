<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Moneysite\Carousel;
use App\Models\Moneysite\Domain;
use App\Models\Moneysite\Promotion;
use App\Models\Moneysite\SeoManagement;
use App\Models\Moneysite\SocialMedia;
use App\Models\Moneysite\WebSetting;
use App\Models\Panel\Menu;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class WebController extends Controller
{
    public function general()
    {
        $web = WebSetting::select('id', 'site_name', 'site_title', 'site_logo', 'favicon', 'marquee', 'proggressive_img', 'unique_code', 'min_deposit', 'max_deposit', 'min_withdrawal', 'max_withdrawal', "is_maintenance")->first();
        return response()->json([
            'success' => true,
            'data'    => $web,
        ]);
    }

    public function updateWebsite(Request $request)
    {
        $validated = $request->validate([
            'site_name'        => 'required|string|max:255',
            'site_title'       => 'required|string|max:255',
            'site_logo'        => 'nullable|url',
            'favicon'          => 'nullable|url',
            'marquee'          => 'nullable|string',
            'proggressive_img' => 'nullable|url',
            'is_maintenance'   => 'nullable|boolean',
        ], [
            // site_name
            'site_name.required'     => 'Site name wajib diisi.',
            'site_name.string'       => 'Site name harus berupa teks.',
            'site_name.max'          => 'Site name maksimal 255 karakter.',

            // site_title
            'site_title.required'    => 'Site title wajib diisi.',
            'site_title.string'      => 'Site title harus berupa teks.',
            'site_title.max'         => 'Site title maksimal 255 karakter.',

            // site_logo
            'site_logo.url'          => 'Site logo harus berupa URL yang valid.',

            // favicon
            'favicon.url'            => 'Favicon harus berupa URL yang valid.',

            // marquee
            'marquee.string'         => 'Marquee harus berupa teks.',

            // proggressive_img
            'proggressive_img.url'   => 'Progressive image harus berupa URL yang valid.',

            // is_maintenance
            'is_maintenance.boolean' => 'Maintenance harus bernilai true atau false.',
        ]);

        try {
            $website = WebSetting::first();

            if (! $website) {
                return response()->json([
                    'success' => false,
                    'message' => 'Website settings not found',
                ], 404);
            }

            $update = $website->update([
                'site_name'        => $validated['site_name'],
                'site_title'       => $validated['site_title'],
                'site_logo'        => $validated['site_logo'],
                'favicon'          => $validated['favicon'],
                'marquee'          => $validated['marquee'],
                'proggressive_img' => $validated['proggressive_img'],
                'is_maintenance'   => $validated['is_maintenance'],
            ]);
            $this->purgeCacheKey('site_config');
            return response()->json([
                'success' => true,
                'message' => 'Website settings updated successfully',
                'data'    => [
                    'site_name'        => $website->site_name,
                    'site_title'       => $website->site_title,
                    'site_logo'        => $website->site_logo,
                    'favicon'          => $website->favicon,
                    'marquee'          => $website->marquee,
                    'proggressive_img' => $website->proggressive_img,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the website settings',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateTransaction(Request $request)
    {
        $validated = $request->validate([
            'unique_code'    => 'nullable|string',
            'min_deposit'    => 'required|string',
            'max_deposit'    => 'required|string',
            'min_withdrawal' => 'required|string',
            'max_withdrawal' => 'required|string',
        ], [
            'unique_code.nullable'    => 'Unique code is optional, but if provided, it must be a valid string.',
            'unique_code.string'      => 'The unique code must be a valid string.',

            'min_deposit.required'    => 'Minimum deposit is required.',
            'min_deposit.string'      => 'Minimum deposit must be a valid string.',

            'max_deposit.required'    => 'Maximum deposit is required.',
            'max_deposit.string'      => 'Maximum deposit must be a valid string.',

            'min_withdrawal.required' => 'Minimum withdrawal is required.',
            'min_withdrawal.string'   => 'Minimum withdrawal must be a valid string.',

            'max_withdrawal.required' => 'Maximum withdrawal is required.',
            'max_withdrawal.string'   => 'Maximum withdrawal must be a valid string.',
        ]);

        try {
            $website = WebSetting::first();

            if (! $website) {
                return response()->json([
                    'success' => false,
                    'message' => 'Website Transaction Rules not found',
                ], 404);
            }

            $update = $website->update([
                'unique_code'    => $validated['unique_code'],
                'min_deposit'    => $validated['min_deposit'],
                'max_deposit'    => $validated['max_deposit'],
                'min_withdrawal' => $validated['min_withdrawal'],
                'max_withdrawal' => $validated['max_withdrawal'],
            ]);

            $this->purgeCacheKey('site_config');

            return response()->json([
                'success' => true,
                'message' => 'Website Transaction Rules updated successfully',
                'data'    => [
                    'unique_code'    => $website->unique_code,
                    'min_deposit'    => $website->min_deposit,
                    'max_deposit'    => $website->max_deposit,
                    'min_withdrawal' => $website->min_withdrawal,
                    'max_withdrawal' => $website->max_withdrawal,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the website Transaction Rules',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function socialMedia()
    {
        $livechat    = WebSetting::select('id', 'url_livechat', 'sc_livechat')->first();
        $socialMedia = SocialMedia::all();
        return response()->json([
            'success' => true,
            'data'    => [
                'livechat'     => $livechat,
                'social_media' => $socialMedia,
            ],
        ]);
    }

    public function updateSocialMediaLivechat(Request $request)
    {
        $validated = $request->validate([
            'url_livechat' => 'nullable|url',
            'sc_livechat'  => 'nullable|string',
        ], [
            'url_livechat.url'   => 'The Url Livechat must be a valid URL.',
            'sc_livechat.string' => 'The Script Livechat must be a valid string.',
        ]);

        try {
            $website = WebSetting::first();

            if (! $website) {
                return response()->json([
                    'success' => false,
                    'message' => 'Website Livechat settings not found.',
                ], 404);
            }

            $update = $website->update([
                'url_livechat' => $validated['url_livechat'],
                'sc_livechat'  => $validated['sc_livechat'],
            ]);

            if ($update) {
                $this->purgeCacheKey('site_config');
                return response()->json([
                    'success' => true,
                    'message' => 'Website Livechat updated successfully.',
                    'data'    => [
                        'url_livechat' => $website->url_livechat,
                        'sc_livechat'  => $website->sc_livechat,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Website Livechat settings.',
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the Website Livechat settings.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSocialMedia(Request $request, string $id)
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'link'        => 'nullable|url',
        ], [
            'link.url'           => 'The Link must be a valid URL.',
            'description.string' => 'The Description must be a valid string.',
        ]);

        try {
            $socialMedia = SocialMedia::find($id);

            if (! $socialMedia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Social Media not found.',
                ], 404);
            }

            $update = $socialMedia->update([
                'description' => $validated['description'],
                'link'        => $validated['link'],
            ]);

            if ($update) {
                $this->purgeCacheKey('social_media_links');
                return response()->json([
                    'success' => true,
                    'message' => 'Social Media updated successfully.',
                    'data'    => [
                        'id'          => $socialMedia->id,
                        'description' => $socialMedia->description,
                        'link'        => $socialMedia->link,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Social Media.',
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the Social Media.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function popupSlider()
    {
        $popup  = WebSetting::select('id', 'popup')->first();
        $slider = Carousel::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'data'    => [
                'popup'  => $popup,
                'slider' => $slider,
            ],
        ]);
    }

    public function updatePopup(Request $request)
    {
        $validated = $request->validate([
            'url' => 'nullable|url',
        ], [
            'url.url' => 'The URL must be a valid URL.',
        ]);

        try {
            $website = WebSetting::first();

            if (! $website) {
                return response()->json([
                    'success' => false,
                    'message' => 'Web settings not found.',
                ], 404);
            }

            $website->update([
                'popup' => $validated['url'],
            ]);
            $this->purgeCacheKey('site_config');
            return response()->json([
                'success' => true,
                'message' => 'Popup URL updated successfully.',
                'data'    => [
                    'popup' => $website->popup,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the Popup URL.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function addSlider(Request $request)
    {
        $validated = $request->validate([
            'url' => 'nullable|url',
        ], [
            'url.url' => 'The URL must be a valid URL.',
        ]);

        try {
            $carousel = Carousel::create([
                'image' => $validated['url'],
            ]);
            $this->purgeCacheKey('carousels_data');
            return response()->json([
                'success' => true,
                'message' => 'Slider URL added successfully.',
                'data'    => $carousel,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the slider.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSlider(string $id)
    {
        try {
            $carousel = Carousel::findOrFail($id);

            $carousel->delete();

            $this->purgeCacheKey('carousels_data');

            return response()->json([
                'success' => true,
                'message' => 'Slider deleted successfully.',
                'data'    => $carousel,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slider not found.',
                'error'   => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the slider.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function theme()
    {
        $theme = WebSetting::first();

        return response()->json([
            'success' => true,
            'data'    => [
                'theme' => $theme->themes,
            ],
        ]);
    }
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|string',
        ], [
            'theme.required' => 'The theme field is required.',
            'theme.string'   => 'The theme must be a valid string.',
        ]);

        try {
            $theme = WebSetting::first();

            if (! $theme) {
                return response()->json([
                    'success' => false,
                    'message' => 'Theme setting not found.',
                ], 404);
            }

            $this->purgeCacheKey('site_config');

            $theme->update([
                'themes' => $validated['theme'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Theme updated successfully.',
                'data'    => [
                    'theme' => $theme->themes,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the theme.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function promotionIndex()
    {
        try {
            $promotion = Promotion::all();
            return response()->json([
                'success' => true,
                'data'    => $promotion,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching promotions',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function promotionStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'cdnImages'   => 'required|string',
            'category'    => 'required|array',
            'endDate'     => 'nullable|string|date',
            'description' => 'nullable|string',
        ], [
            'title.required'     => 'Title is required.',
            'cdnImages.required' => 'Image URL is required.',
            'category.required'  => 'At least one category is required.',
            'category.array'     => 'Category must be an array.',
            'endDate.date'       => 'End date must be a valid date format.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $newPromotion = [
            'title'       => $request->title,
            'cdnImages'   => $request->cdnImages,
            'category'    => implode(', ', $request->category),
            'endDate'     => $request->endDate,
            'description' => $request->description,
        ];

        try {
            $promotion = Promotion::create($newPromotion);

            return response()->json([
                'success' => true,
                'message' => 'Promotion saved successfully!',
                'data'    => $promotion,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the promotion',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function promotionUpdate(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'cdnImages'   => 'required|string',
            'category'    => 'required|array',
            'endDate'     => 'nullable|string|date',
            'description' => 'nullable|string',
        ], [
            'title.required'     => 'Title is required.',
            'cdnImages.required' => 'Image URL is required.',
            'category.required'  => 'At least one category is required.',
            'category.array'     => 'Category must be an array.',
            'endDate.date'       => 'End date must be a valid date format.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $promotion = Promotion::findOrFail($id);

            $updatedPromotion = [
                'title'       => $request->title,
                'cdnImages'   => $request->cdnImages,
                'category'    => implode(', ', $request->category),
                'endDate'     => $request->endDate,
                'description' => $request->description,
            ];

            $promotion->update($updatedPromotion);

            return response()->json([
                'success' => true,
                'message' => 'Promotion updated successfully!',
                'data'    => $promotion,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the promotion',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function promotionDestroy(string $id)
    {
        try {
            $promotion = Promotion::findOrFail($id);

            $promotion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promotion deleted successfully!',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the promotion',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function seoIndex()
    {
        try {
            $seo = SeoManagement::first();

            if (! $seo) {
                return response()->json(['success' => false, 'message' => 'Data not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $seo,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function seoUpdate(Request $request)
    {
        try {
            $seo = SeoManagement::first();

            if (! $seo) {
                return response()->json([
                    'success' => false,
                    'message' => 'SEO data not found',
                ], 404);
            }

            $seo->update($request->all());
            $this->purgeCacheKey('seo_management');
            return response()->json([
                'success' => true,
                'message' => 'SEO data updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function indexDomain()
    {
        $data = Domain::all();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function addDomain(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'custom_title' => 'nullable|string|max:255',
            'meta_tag'     => 'nullable|string',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validated->errors(),
            ], 422);
        }

        $domain = Domain::create([
            'name'         => $request->name,
            'custom_title' => $request->custom_title,
            'meta_tag'     => $request->meta_tag,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Domain added successfully',
            'data'    => $domain,
        ]);
    }

    public function updateDomain(Request $request, $id)
    {
        $domain = Domain::find($id);

        if (! $domain) {
            return response()->json([
                'success' => false,
                'message' => 'Domain not found',
            ], 404);
        }

        $validated = Validator::make($request->all(), [
            'meta_tag' => 'nullable|string',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validated->errors(),
            ], 422);
        }

        try {
            $domain->update([
                'meta_tag' => $request->meta_tag,
            ]);

            $host               = preg_replace('/^www\./', '', $domain->name);
            $cacheKeyDomainMeta = "domain_meta_$host";

            $this->purgeCacheKey($cacheKeyDomainMeta);

            return response()->json([
                'success' => true,
                'message' => 'Domain updated successfully',
                'data'    => $domain,
            ]);
        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update domain',
                $e,
            ], 500);
        }
    }

    public function deleteDomain($id)
    {
        try {
            $domain = Domain::find($id);

            if (! $domain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain not found',
                ], 404);
            }

            $host = preg_replace('/^www\./', '', $domain->name);

            $domain->delete();

            $cacheKeyDomainMeta = "domain_meta_$host";

            $this->purgeCacheKey($cacheKeyDomainMeta);

            return response()->json([
                'success' => true,
                'message' => 'Domain deleted successfully',
            ]);
        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete domain',
            ], 500);
        }
    }

    public function getMenu()
    {
        $admin = Auth::guard('admin')->user();

        $permissions = $admin->getAllPermissions()->pluck('name');

        $menus = Menu::all();

        $buildMenuHierarchy = null;

        $buildMenuHierarchy = function ($parentId = null) use (&$buildMenuHierarchy, $menus, $permissions) {
            return $menus->where('parent_id', $parentId)->map(function ($menu) use ($buildMenuHierarchy, $permissions) {
                if ($menu->permission && ! $permissions->contains($menu->permission)) {
                    return null;
                }

                return array_merge(
                    $menu->toArray(),
                    ['children' => $buildMenuHierarchy($menu->id)]
                );
            })->filter()->values();
        };

        $menuHierarchy = $buildMenuHierarchy();

        return response()->json([
            'success' => true,
            'menus'   => $menuHierarchy,
        ]);
    }

    private function purgeCacheKey(string $key): void
    {
        $admin   = Auth::guard('admin')->user();
        $fullKey = "{$admin->credential->redis_prefix}{$key}";

        config(['database.redis.server_admin' => [
            'host'     => $admin->credential->redis_host,
            'password' => $admin->credential->redis_password,
            'port'     => $admin->credential->redis_port,
            'database' => 1,
        ]]);

        $client = Redis::connection('server_admin')->client();

        try {
            $deleted = $client->del($fullKey);
            if ($deleted > 0) {
                Log::info('Cache key deleted successfully', ['key' => $fullKey]);
            } else {
                Log::warning('Cache key not found or already deleted', ['key' => $fullKey]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to delete cache key', ['key' => $fullKey, 'error' => $e->getMessage()]);
        }
    }

}

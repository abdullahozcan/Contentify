## Changelog - v3.1

**Breaking Changes**: 
- PHP >= 7.2.5 is required!
- `morphto` has been removed from the `Contentify\Models\BaseModel` class
- Laravel's string helper functions are no longer supported, use the `Str` class method instead

**Changes**
- Laravel has been updated from v5.5 to 6.0
- Therefore a couple of small changes have been made to Contentify's code. 
  Please check the diff between version 3.0 and 3.1 to review these changes.  
- Several dependencies (vendor packages) have been updated to newer versions
- The `thujohn/rss-l4` package is no longer maintained and not compatible with Laravel >5.
  Therefore it has been removed as a dependency, but has been included to Contentify's code 
  and now lives in `Contentify\Vendor\Rss`. Check the `Contentify/Vendor/Rss/README.md` to learn more.
- This version includes all Contentify updates since the release of 3.0 (basically bug fixes).
